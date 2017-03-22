<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the Workers.
 */
abstract class PMMIBaseDataQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * Provider name.
   *
   * @var string
   */
  protected $provider = PMMISSOHelper::PROVIDER;

  /**
   * ReportWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service the instance should use.
   * @param Client $http_client
   *   The HTTP Client library.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend interface.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StateInterface $state,
    Client $http_client,
    CacheBackendInterface $cache,
    PMMISSOHelper $sso_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->ssoHelper = $sso_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('http_client'),
      $container->get('cache.default'),
      $container->get('pmmi_sso.helper')
    );
  }

  /**
   * Get member Job Title.
   *
   * @param string $member_id
   *   The MemberMasterCustomer ID.
   * @param int $member_sub_id
   *   The MemberSubCustomer ID.
   *
   * @return string
   *   The job title.
   */
  public function getMemberJobTitle($member_id, $member_sub_id) {
    $job_title = '';
    // Example path: CustomerInfos(MasterCustomerId='00094039',SubCustomerId=0)
    // /Addresses?$filter=AddressStatusCode eq 'GOOD'&$select=JobTitle .
    $path_element = "CustomerInfos(MasterCustomerId='" . $member_id .
      "',SubCustomerId=" . $member_sub_id . ")/Addresses";
    $query = [
      '$filter' => "AddressStatusCode eq 'GOOD' and PrioritySeq eq 0",
      '$select' => 'JobTitle',
    ];
    $request_options = $this->buildGetRequest($path_element, $query);
    if ($job_data = $this->handleRequest($request_options)) {
      empty($job_data[0]->JobTitle) ?: $job_title = $job_data[0]->JobTitle;
    }
    return $job_title;
  }

  /**
   * Helper function for sorting data.
   *
   * @param array $data
   *   The Data array.
   *
   * @return array
   *   The Data array.
   */
  protected function sort(array &$data) {
    foreach ($data as &$value) {
      ksort($value);
    }
    return ksort($data);
  }

  /**
   * Helper function for formatting Json date.
   *
   * @param string $json_date
   *   The Date string.
   * @param string $format
   *   The format string for date output.
   *
   * @return string
   *   The formatted date.
   */
  protected function formatDate($json_date, $format) {
    $timestamp = preg_replace('/[^0-9]/', '', $json_date);
    return date($format, $timestamp / 1000);
  }

  /**
   * Helper function for building GET request.
   *
   * @param string $collection
   *   The requested collection/Path.
   * @param array $query
   *   The array of query parameters.
   *
   * @return array
   *   The request options array.
   */
  protected function buildGetRequest($collection, array $query) {
    $request_options = $this->ssoHelper->buildDataServiceQuery(
      $collection, $query
    );
    $request_options['timeout'] = $this->ssoHelper->getConnectionTimeout();
    return $request_options;
  }

  /**
   * Attempt to handle request to PMMI Personify Services.
   *
   * @param array $request_param
   *   Parameters of the request.
   *
   * @return array
   *   The JSON decoded array of response from PMMI Personify Services.
   */
  protected function handleRequest(array $request_param) {
    $uri = $request_param['uri'];
    $options = $request_param['params'];
    $data = [];
    try {
      $response = $this->httpClient->get($uri, $options);
      $response_data = $response->getBody()->getContents();
      $this->ssoHelper->log("Response received from PMMI Personify server: " . htmlspecialchars($response_data));
      if ($json_data = json_decode($response_data)) {
        $data = $json_data->d;
      }
      else {
        $this->ssoHelper->log("Can't parse Json data from Data Service.");
      }
    }
    catch (RequestException $e) {
      $this->ssoHelper->log('Invalid response from Data Service.');
      return $data;
    }
    return $data;
  }
//  /**
//   * Handler for the update item.
//   *
//   * @param string $worker
//   *   Worker name.
//   * @param array $item
//   *   The $item which was stored in the cron queue.
//   */
//  protected function handleItem($worker, array $item) {
//    $personify_id = $item['pid'];
//    if ($worker == 'users') {
//      /** @var \Drupal\user\Entity\User $account */
//      $account = $this->entityTypeManager->getStorage('user')
//        ->load($item['uid']);
//      $user_data = $this->userData->get($this->provider, $account->id(), 'last_update_data');
//      if (isset($item['last_update_data']['block'])) {
//        $this->getSsoData($personify_id, $account, $user_data);
//      }
//      if (isset($item['last_update_data']['info'])) {
//        $this->getServiceData($personify_id, $account, $user_data);
//      }
//      if (isset($item['last_update_data']['roles'])) {
//        $allowed_ims_roles = $this->ssoHelper->getAllowedRoles(PMMISSOHelper::IMS);
//        $allowed_data_roles = $this->ssoHelper->getAllowedRoles(PMMISSOHelper::DATA);
//
//        // Get all user roles.
//        $exist_drupal_roles = $account->getRoles(TRUE);
//
//        // Get clean drupal roles for user without allowed IMS and Data Service
//        // roles.
//        $drupal_ims_roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::IMS, array_map('strtolower', $allowed_ims_roles));
//        $drupal_data_roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::DATA, $allowed_data_roles);
//        $not_mapped_drupal_roles = array_diff(
//          $exist_drupal_roles,
//          $drupal_ims_roles,
//          $drupal_data_roles
//        );
//
//        // Get roles from IM Service.
//        $ims_roles = $this->getImsRole($personify_id, $allowed_ims_roles);
//
//        // Check Data Service Role.
//        $data_roles = $this->checkDataServiceRole($personify_id, $allowed_data_roles);
//
//        $user_roles_after_check = array_unique(array_merge($not_mapped_drupal_roles, $ims_roles, $data_roles));
//        // Check if two arrays are equal.
//        if (
//          count($exist_drupal_roles) == count($user_roles_after_check) &&
//          array_diff($exist_drupal_roles, $user_roles_after_check) === array_diff($user_roles_after_check, $exist_drupal_roles)
//        ) {
//          $this->ssoHelper->log('User roles have not changed.');
//        }
//        else {
//          $account->set('roles', $user_roles_after_check);
//        }
//        $user_data['roles'] = REQUEST_TIME;
//      }
//      $account->save();
//      $this->userData->set($this->provider, $account->id(), 'last_update_data', $user_data);
//    }
//    if ($worker == 'pc') {
//      /** @var \Drupal\pmmi_sso\Entity\PMMIPersonifyCompanyInterface $company */
//      $company = $this->entityTypeManager->getStorage('pmmi_personify_company')
//        ->load($item['id']);
//      $this->getCompanyData($personify_id, $company);
//      $company->setChangedTime(REQUEST_TIME);
//      $company->save();
//    }
//  }
//
//  /**
//   * Get User Data(SSO Service) that already registered via PMMI SSO.
//   *
//   * @param string $personify_id
//   *   The Personify user ID.
//   * @param User $account
//   *   The user account.
//   * @param array $user_data
//   *   The array of last update timestamp.
//   */
//  public function getSsoData($personify_id, User &$account, array &$user_data) {
//    $raw_user_id = $personify_id . '|0';
//    $request_options = $this->ssoHelper->buildSsoServiceQuery(
//      'SSOCustomerGet',
//      ['vu', 'vp'],
//      ['TIMSSCustomerId' => $raw_user_id]
//    );
//    $request_options['method'] = 'POST';
//    $response = $this->handleRequest($request_options);
//    if ($response instanceof RequestException) {
//      $this->ssoHelper->log("Error with request to get User Data: " . $response->getMessage());
//      return;
//    }
//    $this->parser->setData($response);
//    // Check if user exists and is active.
//    if (
//      $this->parser->validateBool('//m:UserExists') &&
//      !$this->parser->validateBool('//m:DisableAccountFlag')
//    ) {
//      // Parse and set user name.
//      if ($username = $this->parser->getSingleValue('//m:UserName')) {
//        $username == $account->getInitialEmail() ?: $account->set('init', $username);
//        $account->isActive() ?: $account->activate();
//      }
//      else {
//        $account->block();
//        $this->ssoHelper->log($this->t('User: @user not exist or is disabled.', ['@user' => $account->getInitialEmail()]));
//        return;
//      }
//      // Parse and set user email.
//      if ($email = $this->parser->getSingleValue('//m:Email')) {
//        $email == $account->getEmail() ?: $account->setEmail($email);
//      }
//      $user_data['block'] = REQUEST_TIME;
//    }
//    else {
//      $account->block();
//      $this->ssoHelper->log('User does not exist or is disabled.');
//      return;
//    }
//  }
//
//  /**
//   * Get User Data(Data Service) that already registered via PMMI SSO.
//   *
//   * @param string $personify_id
//   *   The Personify user ID.
//   * @param User $account
//   *   The user account.
//   * @param array $user_data
//   *   The array of last update timestamp.
//   */
//  public function getServiceData($personify_id, User &$account, array &$user_data) {
//    $request_options = $this->ssoHelper->buildDataServiceQuery(
//      'CustomerInfos',
//      [
//        '$filter' => "MasterCustomerId eq '$personify_id'",
//        '$select' => "LabelName, FirstName, LastName",
//      ]
//    );
//    $request_options['method'] = 'GET';
//    $response = $this->handleRequest($request_options);
//    if ($response instanceof RequestException) {
//      $this->ssoHelper->log("Error with request to get User Data: " . $response->getMessage());
//      return;
//    }
//    // Check if user data exists.
//    if ($json_data = json_decode($response)) {
//      $data = $json_data->d[0];
//      // Parse and set user LabelName.
//      if ($label_name = $data->LabelName) {
//        $label_name == $account->getAccountName() ?: $account->set('name', $label_name);
//      }
//      else {
//        $account->block();
//        $this->ssoHelper->log('User name not exist or is disabled.');
//        return;
//      }
//      // Parse and set user FirstName.
//      if ($first_name = $data->FirstName) {
//        $first_name == $account->get('field_first_name') ?: $account->set('field_first_name', $first_name);
//      }
//      // Parse and set user LastName.
//      if ($last_name = $data->LastName) {
//        $last_name == $account->get('field_last_name') ?: $account->set('field_last_name', $last_name);
//      }
//      $user_data['info'] = REQUEST_TIME;
//    }
//    else {
//      $this->ssoHelper->log('Invalid response from SSO Service.');
//      return;
//    }
//  }
//
//  /**
//   * Get user roles (IM Service) that already registered via PMMI SSO.
//   *
//   * @param string $personify_id
//   *   The Personify user ID.
//   * @param array $allowed_roles
//   *   The array of allowed IMS roles.
//   *
//   * @return array
//   *   The array of User IMS Roles.
//   */
//  public function getImsRole($personify_id, array $allowed_roles) {
//    // Get the IMS roles that are allowed to register.
//    $ims_role_mapping = array_map('strtolower', $allowed_roles);
//    $user_roles = [];
//    if (empty($ims_role_mapping)) {
//      return $user_roles;
//    }
//    $raw_user_id = $personify_id . '|0';
//    $request_options = $this->ssoHelper->buildSsoServiceQuery(
//      'IMSCustomerRoleGetByTimssCustomerId',
//      ['vu', 'vp'],
//      ['TIMSSCustomerId' => $raw_user_id],
//      TRUE
//    );
//    $request_options['method'] = 'POST';
//    $response = $this->handleRequest($request_options);
//    if ($response instanceof RequestException) {
//      $this->ssoHelper->log("Error with request to get IMS User Roles: " . $response->getMessage());
//      return $user_roles;
//    }
//    $this->parser->setData($response);
//    // Check if user has IMS Role.
//    if ($this->parser->getNodeList('//m:CustomerRoles')->length > 0) {
//      // Parse existing user Roles.
//      $roles = array_map('strtolower', $this->parser->getMultipleValues('//m:CustomerRoles/m:Role/m:Value'));
//      $exist_roles = array_intersect($ims_role_mapping, $roles);
//      if (count($exist_roles) > 0) {
//        $user_roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::IMS, $exist_roles);
//      }
//      else {
//        $this->ssoHelper->log('User does not have allowed IMS Role.');
//      }
//    }
//    return $user_roles;
//  }
//
//  /**
//   * Check if the user is on the committee.
//   *
//   * @param string $personify_id
//   *   The Personify user ID.
//   * @param array $allowed_roles
//   *   The array of allowed Data Service committee IDs.
//   *
//   * @return array
//   *   The array of User Data Service Roles.
//   */
//  public function checkDataServiceRole($personify_id, array $allowed_roles) {
//    // Get the Data Service committee_id that are allowed to register.
//    $user_roles = [];
//    $roles = [];
//    $date = new \DateTime();
//    foreach ($allowed_roles as $committee_id) {
//      $query = [
//        '$filter' => 'MemberMasterCustomer eq \'' . $personify_id . '\' and ' .
//          'CommitteeMasterCustomer eq \'' . $committee_id . '\' and ' .
//          'EndDate ge datetime\'' . $date->format('Y-m-d') . '\' and ' .
//          'ParticipationStatusCodeString eq \'ACTIVE\'',
//      ];
//      $request_options = $this->ssoHelper->buildDataServiceQuery(
//        'CommitteeMembers',
//        $query
//      );
//      $request_options['method'] = 'GET';
//      $response = $this->handleRequest($request_options);
//      if ($response instanceof RequestException) {
//        $this->ssoHelper->log("Error with request to check Data Service User role: " . $response->getMessage());
//        return $user_roles;
//      }
//      elseif ($json_data = json_decode($response)) {
//        $data = $json_data->d;
//        if (!empty($data) && $data[0]->MemberMasterCustomer == $committee_id) {
//          $roles[] = $committee_id;
//        }
//        else {
//          $this->ssoHelper->log('User does not have allowed Data Service Role.');
//        }
//      }
//      else {
//        $this->ssoHelper->log('Wrong response from Data Service.');
//      }
//    }
//    if (!empty($roles)) {
//      $user_roles = $this->ssoHelper->filterAllowedRoles(PMMISSOHelper::DATA, $roles);
//    }
//    return $user_roles;
//  }
//
//  /**
//   * Get Company Data.
//   *
//   * @param string $personify_id
//   *   The Personify company ID.
//   * @param PMMIPersonifyCompanyInterface $company
//   *   The user account.
//   */
//  public function getCompanyData($personify_id, PMMIPersonifyCompanyInterface &$company) {
//    $request_options = $this->ssoHelper->buildDataServiceQuery(
//      'CustomerInfos',
//      [
//        '$filter' => "MasterCustomerId eq '$personify_id'",
//        '$select' => "MasterCustomerId, LabelName, CustomerClassCode",
//      ]
//    );
//    $request_options['method'] = 'GET';
//    $response = $this->handleRequest($request_options);
//    if ($response instanceof RequestException) {
//      $this->ssoHelper->log("Error with request to get Company Data: " . $response->getMessage());
//      return;
//    }
//    // Check if company data exists.
//    if ($json_data = json_decode($response)) {
//      $data = $json_data->d[0];
//      // Parse and set company Name.
//      if ($label_name = $data->LabelName) {
//        $label_name == $company->label() ?: $company->set('name', $label_name);
//        // Change status for the company to publish if company unpublished.
//        $company->isPublished() ?: $company->setPublished(TRUE);
//      }
//      else {
//        $company->setPublished(FALSE);
//        $this->ssoHelper->log('Company name does not exist or is disabled.');
//        return;
//      }
//      // Parse and set company CustomerClassCode.
//      if ($code = $data->CustomerClassCode) {
//        $code == $company->get('code') ?: $company->set('code', $code);
//      }
//    }
//    else {
//      $this->ssoHelper->log('Invalid response from SSO Service.');
//      return;
//    }
//  }
//
//  /**
//   * Attempt to handle request to PMMI Personify Services.
//   *
//   * @param array $request_param
//   *   Parameters of the request.
//   *
//   * @return string|RequestException
//   *   The response data from PMMI Personify Services.
//   */
//  protected function handleRequest(array $request_param) {
//    $method = $request_param['method'];
//    $uri = $request_param['uri'];
//    if ($method == 'POST') {
//      $options = ['form_params' => $request_param['params']];
//    }
//    else {
//      $options = $request_param['params'];
//    }
//    try {
//      $response = $this->httpClient->request($method, $uri, $options);
//      $response_data = $response->getBody()->getContents();
//      $this->ssoHelper->log("Data received from PMMI Personify server: " . htmlspecialchars($response_data));
//    }
//    catch (RequestException $e) {
//      return $e;
//    }
//    return $response_data;
//  }

}
