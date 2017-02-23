<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\pmmi_sso\Event\PMMISSOPreLoginEvent;
use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Event\PMMISSOPreUserLoadEvent;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Email address for new users is combo of username + custom hostname.
   *
   * @var int
   */
  const EMAIL_ASSIGNMENT_STANDARD = 0;

  /**
   * Email address for new users is derived from a PMMI SSO attirbute.
   *
   * @var int
   */
  const EMAIL_ASSIGNMENT_ATTRIBUTE = 1;

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
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
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
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Used to dispatch PMMI SSO login events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  protected $provider = 'pmmi_sso';

  /**
   * PMMISSOUserManager constructor.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth interface.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $settings
   *   The settings.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    ExternalAuthInterface $external_auth,
    AuthmapInterface $authmap,
    ConfigFactoryInterface $settings,
    SessionInterface $session,
    Connection $database_connection,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->externalAuth = $external_auth;
    $this->authmap = $authmap;
    $this->settings = $settings;
    $this->session = $session;
    $this->connection = $database_connection;
    $this->eventDispatcher = $event_dispatcher;
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
  public function register($authname, array $property_values = []) {
    try {
      $property_values['pass'] = $this->randomPassword();
      $user = $this->externalAuth->register($authname, $this->provider, $property_values);
    } catch (ExternalAuthRegisterException $e) {
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
  public function login(PMMISSOPropertyBag $property_bag, $token) {
    // Dispatch an event that allows modules to alter any of the PMMI SSO data
    // before it's used to lookup a Drupal user account via the authmap table.
    $this->eventDispatcher->dispatch(PMMISSOHelper::EVENT_PRE_USER_LOAD, new PMMISSOPreUserLoadEvent($property_bag));

    $account = $this->externalAuth->load($property_bag->getUsername(), $this->provider);
    if ($account === FALSE) {
      // Check if we should create the user or not.
      $config = $this->settings->get('pmmi_sso.settings');
      // Dispatch an event that allows modules to deny automatic registration
      // for this user account or to set properties for the user that will
      // be created.
      $sso_pre_register_event = new PMMISSOPreRegisterEvent($property_bag);
      $sso_pre_register_event->setPropertyValue('mail', $this->getEmailForNewAccount($property_bag));
      $this->eventDispatcher->dispatch(PMMISSOHelper::EVENT_PRE_REGISTER, $sso_pre_register_event);
      if ($sso_pre_register_event->getAllowAutomaticRegistration()) {
        $account = $this->register($sso_pre_register_event->getDrupalUsername(), $sso_pre_register_event->getPropertyValues());
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

    $this->externalAuth->userLoginFinalize($account, $property_bag->getUsername(), $this->provider);
    $this->storeLoginSessionData($this->session->getId(), $token);
  }

  /**
   * Store the Session ID and token for single-log-out purposes.
   *
   * @param string $session_id
   *   The session ID, to be used to kill the session later.
   * @param string $token
   *   The PMMI SSO service token to be used as the lookup key.
   */
  protected function storeLoginSessionData($session_id, $token) {
    if ($this->settings->get('pmmi_sso.settings')
        ->get('logout.enable_single_logout') === TRUE
    ) {
      $this->connection->insert('pmmi_sso_login_data')
        ->fields(
          array('sid', 'plainsid', 'token', 'created'),
          array(Crypt::hashBase64($session_id), $session_id, $token, time())
        )
        ->execute();
    }
  }

  /**
   * Return PMMI SSO username for account, or FALSE if it doesn't have one.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool|string
   *   The PMMI SSO username if it exists, or FALSE otherwise.
   */
  public function getSsoUsernameForAccount($uid) {
    return $this->authmap->get($uid, 'pmmi_sso');
  }

  /**
   * Return uid of account associated with passed in PMMI SSO username.
   *
   * @param string $sso_username
   *   The PMMI SSO username to lookup.
   *
   * @return bool|int
   *   The uid of the user associated with the $sso_username, FALSE otherwise.
   */
  public function getUidForSsoUsername($sso_username) {
    return $this->authmap->getUid($sso_username, 'pmmi_sso');
  }

  /**
   * Save an association of the passed in Drupal user account and SSO username.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   * @param string $sso_username
   *   The PMMI SSO username.
   */
  public function setSsoUsernameForAccount(UserInterface $account, $sso_username) {
    $this->authmap->save($account, 'pmmi_sso', $sso_username);
  }

  /**
   * Remove the PMMI SSO username association with the provided user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   */
  public function removeSsoUsernameForAccount(UserInterface $account) {
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

  /**
   * Return the email address that should be assigned to an auto-register user.
   *
   * @param \Drupal\pmmi_sso\PMMISSOPropertyBag $sso_property_bag
   *   The PMMISSOPropertyBag associated with the user's login attempt.
   *
   * @return string
   *   The email address.
   *
   * @throws \Drupal\pmmi_sso\Exception\PMMISSOLoginException
   *   Thrown when the email address cannot be derived properly.
   */
  public function getEmailForNewAccount(PMMISSOPropertyBag $sso_property_bag) {
    $email_assignment_strategy = $this->settings->get('pmmi_sso.settings')
      ->get('user_accounts.email_assignment_strategy');
    if ($email_assignment_strategy === self::EMAIL_ASSIGNMENT_STANDARD) {
      return $sso_property_bag->getUsername() . '@' . $this->settings->get('pmmi_sso.settings')
          ->get('user_accounts.email_hostname');
    }
    elseif ($email_assignment_strategy === self::EMAIL_ASSIGNMENT_ATTRIBUTE) {
      $email_attribute = $this->settings->get('pmmi_sso.settings')
        ->get('user_accounts.email_attribute');
      if (empty($email_attribute) || !array_key_exists($email_attribute, $sso_property_bag->getAttributes())) {
        throw new PMMISSOLoginException('Specified PMMI SSO email attribute does not exist.');
      }

      $val = $sso_property_bag->getAttributes()[$email_attribute];
      if (empty($val)) {
        throw new PMMISSOLoginException('Empty data found for PMMI SSO email attribute.');
      }

      // The attribute value may actually be an array of values, but we need it
      // to only contain 1 value.
      if (is_array($val) && count($val) !== 1) {
        throw new PMMISSOLoginException('Specified PMMI SSO email attribute was formatted in an unexpected way.');
      }

      if (is_array($val)) {
        $val = $val[0];
      }

      return trim($val);
    }
    else {
      throw new PMMISSOLoginException('Invalid email address assignment type for auto user registration specified in settings.');
    }
  }

}
