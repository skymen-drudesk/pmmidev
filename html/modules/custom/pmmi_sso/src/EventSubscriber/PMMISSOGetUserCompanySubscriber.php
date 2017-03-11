<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\pmmi_sso\Event\PMMISSOPreLoginEvent;
use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\pmmi_sso\Exception\PMMISSOServiceException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PMMISSOGetUserCompanySubscriber.
 */
class PMMISSOGetUserCompanySubscriber implements EventSubscriberInterface {


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
    $events[PMMISSOHelper::EVENT_PRE_LOGIN][] = ['getUserCompanies', 100];
    return $events;
  }

  /**
   * Get user related company.
   *
   * @param PMMISSOPreLoginEvent $event
   *   The event object.
   *
   * @throws PMMISSOLoginException
   *   Thrown if there was a problem with login.
   * @throws PMMISSOServiceException
   *   Thrown if there was a problem with request.
   */
  public function getUserCompanies(PMMISSOPreLoginEvent $event) {
    $account = $event->getAccount();
    if ($account->isNew()) {

    }
    // Get the Data Service committee_id that are allowed to register.
    $role_id_mapping = $this->ssoHelper->getAllowedRoles(PMMISSOHelper::DATA);
    if (empty($role_id_mapping) && empty($event->getDrupalRoles())) {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOLoginException("User does not have any allowed roles.");
    }
    $user_id = $event->getSsoPropertyBag()->getUserId();
    $date = new \DateTime();
    foreach ($role_id_mapping as $committee_id) {
      $query = [
        '$filter' => 'MemberMasterCustomer eq \'' . $user_id . '\' and ' .
        'CommitteeMasterCustomer eq \'' . $committee_id . '\' and ' .
        'EndDate ge datetime\'' . $date->format('Y-m-d') . '\' and ' .
        'ParticipationStatusCodeString eq \'ACTIVE\'',
      ];
      $request_options = $this->ssoHelper->buildDataServiceQuery(
        'CommitteeMembers',
        $query
      );
      $request_options['method'] = 'GET';
      $response = $this->handleRequest($request_options);
      if ($response instanceof RequestException) {
        throw new PMMISSOServiceException("Error with request to check Data Service User role: ", $response->getMessage());
      }
      elseif ($json_data = json_decode($response)) {
        $data = $json_data->d;
        if (!empty($data) && $data[0]->MemberMasterCustomer == $committee_id) {
          $roles[] = $committee_id;
        }
        else {
          $this->ssoHelper->log('Wrong response from Data Service.');
        }
      }
      else {
        $this->ssoHelper->log('User does not have allowed Data Service Role.');
      }
    }
    if (!empty($roles)) {
      $roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::DATA, $roles);
      $event->setDrupalRoles($roles);
    }
    $roles_to_register = $event->getDrupalRoles();
    if (!empty($roles_to_register)) {
      $event->setPropertyValue('roles', $roles_to_register);
    }
    else {
      $event->setAllowAutomaticRegistration(FALSE);
      throw new PMMISSOLoginException("User does not have any allowed roles.");
    }
  }

  /**
   * Attempt to handle request to PMMI Personify Services.
   *
   * @param array $request_param
   *   Parameters of the request.
   *
   * @throws RequestException
   *   Thrown if there was a problem with request.
   *
   * @return string|RequestException
   *   The response data from PMMI Personify Services.
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
