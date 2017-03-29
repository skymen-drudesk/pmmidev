<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pmmi_psdata\Service\PMMIDataCollector;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise;
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
   * @var \GuzzleHttp\ClientInterface
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
   * Drupal\pmmi_psdata\Service\PMMIDataCollector definition.
   *
   * @var \Drupal\pmmi_psdata\Service\PMMIDataCollector
   */
  protected $dataCollector;

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
   * @param ClientInterface $http_client
   *   The HTTP Client library.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend interface.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param PMMIDataCollector $psdata_collector
   *   The PMMIDataCollector service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StateInterface $state,
    ClientInterface $http_client,
    CacheBackendInterface $cache,
    PMMISSOHelper $sso_helper,
    PMMIDataCollector $psdata_collector
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->ssoHelper = $sso_helper;
    $this->dataCollector = $psdata_collector;
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
      $container->get('pmmi_sso.helper'),
      $container->get('pmmi_psdata.collector')
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
   * Helper function for adding filter to the query.
   *
   * @param string $operator
   *   The comparison operator.
   * @param string $property
   *   The property of the added filter.
   * @param array $values
   *   The array of values.
   * @param bool $first
   *   Indicates that this is the first element of the query.
   * @param bool $wrap
   *   Wrap in quotation marks.
   *
   * @return string
   *   The query string for property.
   */
  protected function addFilter($operator, $property, array $values, $first = FALSE, $wrap = FALSE) {
    $count = count($values);
    if ($count == 0) {
      return '';
    }
    $query = '';
    if ($count > 1) {
      $query = $first ? '(' : 'and (';
      $counter = 0;
      foreach ($values as $value) {
        $counter++;
        $wrap ?: $value = "'$value'";
        if ($counter == $count) {
          $query .= "$property $operator $value) ";
        }
        else {
          $query .= "$property $operator $value or ";
        }
      }
    }
    else {
      $query .= $first ? '' : 'and ';
      $value = $wrap ? $values[0] : "'$values[0]'";
      $query .= "$property eq $value ";
    }
    return $query;
  }

  /**
   * Helper function for creating a request for information about employees.
   *
   * @param array $ids
   *   An array of member IDs.
   *
   * @return array
   *   The request options array.
   */
  protected function buildMembersInfoRequest(array $ids) {
    // CustomerInfos?$filter=(MasterCustomerId eq '00026974' or MasterCustomerId
    // eq '12081383') &$select=MasterCustomerId,LabelName,FirstName,LastName .
    $path_element = 'CustomerInfos';
    $filter = $this->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,LabelName,FirstName,LastName',
    ];
    return $this->buildGetRequest($path_element, $query);
  }

  /**
   * Helper function for creating a request for a job title for employees.
   *
   * @param array $ids
   *   An array of member IDs.
   *
   * @return array
   *   The request options array.
   */
  protected function buildAddressRequest(array $ids) {
    // AddressInfos?$select=MasterCustomerId,JobTitle&$filter=
    // (MasterCustomerId eq '00000159' or MasterCustomerId eq '00000357' or
    // MasterCustomerId eq '00000375') and PrioritySeq eq 0 .
    $path_element = 'AddressInfos';
    $filter = $this->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $filter .= $this->addFilter('eq', 'PrioritySeq', [0], FALSE, TRUE);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,JobTitle,CountryCode',
    ];
    return $this->buildGetRequest($path_element, $query);
  }

  /**
   * Building a request for communication information about employees.
   *
   * @param array $ids
   *   An array of member IDs.
   * @param array $types
   *   An array of required types of communications.
   *
   * @return array
   *   The request options array.
   */
  protected function buildCommunicationRequest(array $ids, array $types) {
    // CusCommunications?$filter=(MasterCustomerId eq '00000357' or
    // MasterCustomerId eq '00000159') and CommLocationCodeString eq 'WORK' and
    // (CommTypeCodeString eq 'EMAIL' or CommTypeCodeString eq 'PHONE')
    // &$select=CommTypeCodeString,FormattedPhoneAddress,CountryCode .
    $path_element = 'CusCommunications';
    $filter = $this->addFilter('eq', 'MasterCustomerId', $ids, TRUE);
    $filter .= $this->addFilter('eq', 'CommTypeCodeString', $types);
    $filter .= $this->addFilter('eq', 'CommLocationCodeString', ['WORK']);
    $query = [
      '$filter' => $filter,
      '$select' => 'MasterCustomerId,CommTypeCodeString,FormattedPhoneAddress,CountryCode',
    ];
    return $this->buildGetRequest($path_element, $query);
  }

  /**
   * Helper function for separating requests.
   *
   * @param array $ids
   *   An array of member IDs.
   * @param string $collection
   *   The requested collection.
   * @param array $options
   *   An additional array of requested options.
   *
   * @return array
   *   The requests options array.
   */
  protected function separateRequest(array $ids, $collection, $options = []) {
    $requests_options = [];
    $chunked = array_chunk($ids, 20);
    foreach ($chunked as $chunk) {
      switch ($collection) {
        case 'info':
          $requests_options[] = $this->buildMembersInfoRequest($chunk);
          break;

        case 'address':
          $requests_options[] = $this->buildAddressRequest($chunk);
          break;

        case 'communication':
          $requests_options[] = $this->buildCommunicationRequest($chunk, $options);
          break;
      }
    }
    return $requests_options;
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
   * Attempt to handle requests to PMMI Personify Services.
   *
   * @param array $requests
   *   An array of queries parameters.
   *
   * @return array
   *   The JSON decoded array of responses from PMMI Personify Services.
   */
  protected function handleAsyncRequests(array $requests) {
    $data = [];
    $promises = [];
    // Initiate each request but do not block.
    foreach ($requests as $request) {
      $uri = $request['uri'];
      $options = $request['params'];
      $promises[] = $this->httpClient->getAsync($uri, $options);
    }
    try {
      // Wait on all of the requests to complete. Throws a ConnectException
      // if any of the requests fail.
      $results = Promise\unwrap($promises);
      $this->ssoHelper->log("Response received from PMMI Personify server.");
    }
    catch (ConnectException $e) {
      $this->ssoHelper->log('Invalid response from Data Service.');
      return $data;
    }
    /** @var \GuzzleHttp\Psr7\Response $response */
    foreach ($results as $response) {
      $response_data = $response->getBody()->getContents();
      if ($json_data = json_decode($response_data)) {
        $data = array_merge($data, $json_data->d);
      }
      else {
        $this->ssoHelper->log("Can't parse Json data from Data Service.");
      }
    }
    return $data;
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

}
