<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\pmmi_sso\Exception\PMMISSOServiceException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
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
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['getSsoData', 100];
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['getServiceData', 99];
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
  public function getSsoData(PMMISSOPreRegisterEvent $event) {
    $raw_user_id = $event->getSsoPropertyBag()->getRawUserId();
    $request_options = $this->ssoHelper->buildSsoServiceQuery(
      'SSOCustomerGet',
      ['vu', 'vp'],
      ['TIMSSCustomerId' => $raw_user_id]
    );
    $request_options['method'] = 'POST';
    $response = $this->handleRequest($request_options);
    if ($response instanceof RequestException) {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOServiceException("Error with request to get User Data: ", $response->getMessage());
    }
    $this->parser->setData($response);
    // Check if user exist and active.
    if ($this->parser->validateBool('//m:UserExists') && !$this->parser->validateBool('//m:DisableAccountFlag')) {
      // Parse and set user name.
      if ($username = $this->parser->getSingleValue('//m:UserName')) {
        $event->setDrupalUsername($username);
        $event->setPropertyValue('init', $username);
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
  public function getServiceData(PMMISSOPreRegisterEvent $event) {
    $user_id = $event->getSsoPropertyBag()->getUserId();
    $request_options = $this->ssoHelper->buildDataServiceQuery(
      'CustomerInfos',
      ['$filter' => "MasterCustomerId eq '$user_id'"]
    );
    $request_options['method'] = 'GET';
    $response = $this->handleRequest($request_options);
    if ($response instanceof RequestException) {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOServiceException("Error with request to get User Data: ", $response->getMessage());
    }
//    $data = json_decode($response);
    // Check if user exist and active.
    if ($json_data = json_decode($response)) {
      $data = $json_data->d[0];
      // Parse and set user name.
      if ($label_name = $data->LabelName) {
        $event->setPropertyValue('name', $label_name);
        $event->setAuthData('label_name', $label_name);
      }
      else {
        $event->setAllowAutomaticRegistration(FALSE);
        throw new PMMISSOLoginException("User name not exist or disabled.");
      }
      // Parse and set user email.
      if ($first_name = $data->FirstName) {
        $event->setPropertyValue('field_first_name', $first_name);
        $event->setAuthData('first_name', $first_name);
      }
      // Parse and set user email.
      if ($last_name = $data->LastName) {
        $event->setPropertyValue('field_last_name', $last_name);
        $event->setAuthData('last_name', $last_name);
      }
    }
    else {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOLoginException("User does not exist or disabled.");
    }
  }

  /**
   * @inheritdoc
   */
  protected function handleRequest(array $request_param) {
    $method = $request_param['method'];
    $uri = $request_param['uri'];
    if ($method == 'POST') {
      $options = ['form_params' => $request_param['params']];
    }
    else {
      $options = $request_param['params'];
    }

    try {
      $response = $this->httpClient->request($method, $uri, $options);
      $response_data = $response->getBody()->getContents();
      $this->ssoHelper->log("User Data received from PMMI Personify server: " . htmlspecialchars($response_data));
    }
    catch (RequestException $e) {
      return $e;
    }
    return $response_data;
  }

}


//    try {
//      $response = $this->httpClient->request(
//        'POST',
//        $query_options['uri'],
//        ['form_params' => $query_options['params']]
//      );
//      $response_data = $response->getBody()->getContents();
//      $this->ssoHelper->log("User Data received from PMMI SSO server: " . htmlspecialchars($response_data));
//    }
//    catch (RequestException $e) {
//      $event->setAllowAutomaticRegistration(FALSE);
//      throw new PMMISSOServiceException("Error with request to get User Data: " . $e->getMessage());
//    }