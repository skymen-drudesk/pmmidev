<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\pmmi_sso\Exception\PMMISSOServiceException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PMMISSOGetUserDataSubscriber.
 */
class PMMISSOGetUserDataSubscriber implements EventSubscriberInterface {


  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * Stores PMMISSOXML parser.
   *
   * @var \Drupal\pmmi_sso\Parsers\PMMISSOXmlParser
   */
  protected $parser;

  /**
   * PMMISSOAutoAssignRoleSubscriber constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param PMMISSOXmlParser $parser
   *   The PMMI SSO XML parser service.
   */
  public function __construct(Client $http_client, PMMISSOHelper $sso_helper, PMMISSOXmlParser $parser) {
    $this->httpClient = $http_client;
    $this->ssoHelper = $sso_helper;
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['getUserData', 100];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Get User Data that just registered via PMMI SSO.
   *
   * @param PMMISSOPreRegisterEvent $event
   *   The event object.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem with login.
   * @throws PMMISSOServiceException
   *   Thrown if there was a problem with request.
   */
  public function getUserData(PMMISSOPreRegisterEvent $event) {
    $raw_user_id = $event->getSsoPropertyBag()->getRawUserId();
    $query_options = $this->ssoHelper->buildServiceQuery(
      'SSOCustomerGet',
      ['vu', 'vp'],
      ['TIMSSCustomerId' => $raw_user_id]
    );
    try {
      $response = $this->httpClient->request(
        'POST',
        $query_options['uri'],
        ['form_params' => $query_options['params']]
      );
      $response_data = $response->getBody()->getContents();
      $this->ssoHelper->log("User Data received from PMMI SSO server: " . htmlspecialchars($response_data));
    }
    catch (RequestException $e) {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOServiceException("Error with request to get User Data: " . $e->getMessage());
    }
    $this->parser->setData($response_data);
    // Check if user exist and active.
    if ($this->parser->validateBool('//m:UserExists') && !$this->parser->validateBool('//m:DisableAccountFlag')) {
      // Parse and set user name.
      if ($username = $this->parser->getSingleValue('//m:UserName')) {
        $event->setDrupalUsername($username);
      }
      else {
        $event->setAllowAutomaticRegistration(FALSE);
        throw new PMMISSOLoginException("User name not exist or disabled.");
      }
      // Parse and set user email.
      if ($email = $this->parser->getSingleValue('//m:Email')) {
        $event->setPropertyValue('mail', $email);
      }
    }
    else {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOLoginException("User does not exist or disabled.");
    }
  }

}
