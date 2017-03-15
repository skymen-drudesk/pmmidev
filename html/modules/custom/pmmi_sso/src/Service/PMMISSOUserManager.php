<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pmmi_sso\Event\PMMISSOPreLoginEvent;
use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Event\PMMISSOPreUserLoadEvent;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\pmmi_sso\PMMISSOPropertyBag;
use Drupal\Component\Utility\Crypt;

/**
 * Class PMMISSOUserManager.
 */
class PMMISSOUserManager {

  /**
   * Used to include the externalauth service from the external_auth module.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * An authmap service object.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The immutable configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Used when storing PMMI SSO login data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Used to dispatch PMMI SSO login events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The PMMI SSO token Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tokenStorage;

  protected $provider = 'pmmi_sso';

  /**
   * PMMISSOUserManager constructor.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth interface.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The settings.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    ExternalAuthInterface $external_auth,
    AuthmapInterface $authmap,
    ConfigFactoryInterface $configFactory,
    SessionInterface $session,
    UserDataInterface $user_data,
    EventDispatcherInterface $event_dispatcher,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->externalAuth = $external_auth;
    $this->authmap = $authmap;
    $this->settings = $configFactory->get('pmmi_sso.settings');
    $this->session = $session;
    $this->userData = $user_data;
    $this->eventDispatcher = $event_dispatcher;
    $this->tokenStorage = $entityTypeManager->getStorage('pmmi_sso_token');
  }

  /**
   * Register a local Drupal user given a PMMI SSO username.
   *
   * @param string $authname
   *   The PMMI SSO username.
   * @param array $property_values
   *   Property values to assign to the user on registration.
   *
   * @throws PMMISSOLoginException
   *   When the user account could not be registered.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the newly registered user.
   */
  public function register($authname, array $property_values = [], array $user_data = []) {
    try {
      $property_values['pass'] = $this->randomPassword();
      $user = $this->externalAuth->register($authname, $this->provider, $property_values, $user_data);
    }
    catch (ExternalAuthRegisterException $e) {
      throw new PMMISSOLoginException($e->getMessage());
    }
    return $user;
  }

  /**
   * Attempts to log the user in to the Drupal site.
   *
   * @param PMMISSOPropertyBag $property_bag
   *   PMMISSOPropertyBag containing username and attributes from PMMI SSO.
   * @param string $token
   *   The service token.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem logging in the user.
   */
  public function login(PMMISSOPropertyBag $property_bag, $token = NULL) {
    // Dispatch an event that allows modules to alter any of the PMMI SSO data
    // before it's used to lookup a Drupal user account via the authmap table.
    $this->eventDispatcher->dispatch(PMMISSOHelper::EVENT_PRE_USER_LOAD, new PMMISSOPreUserLoadEvent($property_bag));

    $account = $this->externalAuth->load($property_bag->getUserId(), $this->provider);
    if ($account === FALSE) {
      // Dispatch an event that allows modules to deny automatic registration
      // for this user account or to set properties for the user that will
      // be created.
      $sso_pre_register_event = new PMMISSOPreRegisterEvent($property_bag);
      $this->eventDispatcher->dispatch(PMMISSOHelper::EVENT_PRE_REGISTER, $sso_pre_register_event);
      if ($sso_pre_register_event->getAllowAutomaticRegistration()) {
        $account = $this->register($sso_pre_register_event->getUserId(), $sso_pre_register_event->getPropertyValues(), $sso_pre_register_event->getAuthData());
        $this->userData->set('pmmi_sso', $account->id(), 'last_update_block', REQUEST_TIME);
        $this->userData->set('pmmi_sso', $account->id(), 'last_update_info', REQUEST_TIME);
        $this->userData->set('pmmi_sso', $account->id(), 'last_update_roles', REQUEST_TIME);
      }
      else {
        throw new PMMISSOLoginException("Cannot register user, an event listener denied access.");
      }
    }

    // Dispatch an event that allows modules to prevent this user from logging
    // in and/or alter the user entity before we save it.
    $pre_login_event = new PMMISSOPreLoginEvent($account, $property_bag);
    $this->eventDispatcher->dispatch(PMMISSOHelper::EVENT_PRE_LOGIN, $pre_login_event);

    // Save user entity since event listeners may have altered it.
    $account->save();

    if (!$pre_login_event->getAllowLogin()) {
      throw new PMMISSOLoginException("Cannot login, an event listener denied access.");
    }
    // Check and add relationship for user companies.
    if ($pre_login_event->getUpdateCompanyFlag()) {
      $account->set('field_company', $pre_login_event->getCompanies());
      $account->save();
    }

    $this->externalAuth->userLoginFinalize($account, $property_bag->getUsername(), $this->provider);
    $this->storeUserToken($property_bag->getToken(), $account->id(), $property_bag->getUserId());
  }

  /**
   * Store the Session ID and token for single-log-out purposes.
   *
   * @param string $token
   *   The Token value.
   * @param int $uid
   *   The User ID to be used as the lookup key.
   * @param string $auth_id
   *   The User Auth ID value.
   */
  public function storeUserToken($token, $uid, $auth_id) {
    /** @var \Drupal\pmmi_sso\Entity\PMMISSOTokenInterface $token_entity */
    $token_search = $this->tokenStorage->loadByProperties(['uid' => $uid]);
    $expire_time = time() + $this->settings->get('expiration');
    if ($token_entity = reset($token_search)) {
      $token_entity->setToken($token, $expire_time);
    }
    else {
      $token_entity = $this->tokenStorage->create([
        'uid' => $uid,
        'auth_id' => $auth_id,
        'value' => $token,
        'expire' => $expire_time,
      ]);
    }
    $this->session->set('expiration', $expire_time);
    $token_entity->save();
  }

//  /**
//   * Store the Session ID and token for single-log-out purposes.
//   *
//   * @param string $session_id
//   *   The session ID, to be used to kill the session later.
//   * @param string $token
//   *   The PMMI SSO service token to be used as the lookup key.
//   */
//  protected function storeLoginSessionData($session_id, $token) {
//    if ($this->settings->get('pmmi_sso.settings')
//        ->get('logout.enable_single_logout') === TRUE
//    ) {
//      $this->connection->insert('pmmi_sso_login_data')
//        ->fields(
//          array('sid', 'plainsid', 'token', 'created'),
//          array(Crypt::hashBase64($session_id), $session_id, $token, time())
//        )
//        ->execute();
//    }
//  }

  /**
   * Return PMMI SSO user ID for account, or FALSE if it doesn't have one.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool|string
   *   The PMMI SSO username if it exists, or FALSE otherwise.
   */
  public function getSsoUserIdForAccount($uid) {
    return $this->authmap->get($uid, 'pmmi_sso');
  }

  /**
   * Return uid of account associated with passed in PMMI SSO username.
   *
   * @param string $sso_user_id
   *   The PMMI SSO user ID to lookup.
   *
   * @return bool|int
   *   The uid of the user associated with the $sso_user_id, FALSE otherwise.
   */
  public function getUidForSsoUserId($sso_user_id) {
    return $this->authmap->getUid($sso_user_id, 'pmmi_sso');
  }

  /**
   * Save an association of the passed in Drupal user account and SSO username.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   * @param string $sso_user_id
   *   The PMMI SSO user ID.
   */
  public function setSsoUserIdForAccount(UserInterface $account, $sso_user_id) {
    $this->authmap->save($account, 'pmmi_sso', $sso_user_id);
  }

  /**
   * Remove the PMMI SSO user ID association with the provided user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   */
  public function removeSsoUserIdForAccount(UserInterface $account) {
    $this->authmap->delete($account->id());
  }

  /**
   * Generate a random password for new user registrations.
   *
   * @return string
   *   A random password.
   */
  protected function randomPassword() {
    // Default length is 10, use a higher number that's harder to brute force.
    return \user_password(30);
  }

}
