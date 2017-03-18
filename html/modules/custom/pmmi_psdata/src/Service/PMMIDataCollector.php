<?php

namespace Drupal\pmmi_psdata\Service;

use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\Client;
use Drupal\Core\Cache\DatabaseBackend;
use GuzzleHttp\Exception\RequestException;

/**
 * Class PMMIDataCollector.
 *
 * @package Drupal\pmmi_psdata
 */
class PMMIDataCollector {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Drupal\Core\Cache\DatabaseBackend definition.
   *
   * @var \Drupal\Core\Cache\DatabaseBackend
   */
  protected $cache;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * PMMIDataCollector constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param \Drupal\Core\Cache\DatabaseBackend $cache_default
   *   The cache to use for the Personify service data.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   */
  public function __construct(
    Client $http_client,
    DatabaseBackend $cache_default,
    PMMISSOHelper $sso_helper
  ) {
    $this->httpClient = $http_client;
    $this->cache = $cache_default;
    $this->ssoHelper = $sso_helper;
  }

  /**
   * Get Members Data.
   *
   * @param string $data_type
   *   The type of requested data.
   * @param string $id
   *   The ID of requested data.
   *
   * @return array
   *   The array of data.
   */
  public function getData($data_type, $id) {
    $data = NULL;
    $cid = PMMISSOHelper::PROVIDER . ':' . $data_type . '_' . $id;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    switch ($data_type) {
      case 'committee':
        $data = $this->getCommitteeData($id);
        break;

      case 'about':
        $data = $this->getCommitteeData($id);
        break;
    }
    if ($data) {
      $this->cache->set($cid, $data);
    }
    return $data;
  }

  /**
   * Get Committee Data.
   *
   * @param string $id
   *   The ID of requested committee.
   *
   * @return array
   *   The array of data.
   */
  protected function getCommitteeData($id) {
    $data = NULL;
    $date = new \DateTime();
    $query = [
      '$filter' => "CommitteeMasterCustomer eq '$id' and " .
        "ParticipationStatusCodeString eq 'ACTIVE' and EndDate ge datetime'" .
        $date->format('Y-m-d') . "'",
    ];
    $request_options = $this->buildGetRequest('CommitteeMembers', $query);

    if ($committee_data = $this->handleRequest($request_options)) {
      foreach ($committee_data as $customer) {
        $last_first_name = $customer->CommitteeMemberLastFirstName;
        $member_id = $customer->MemberMasterCustomer;
        $member_sub_id = $customer->MemberSubCustomer;
        $position = $customer->PositionCodeDescriptionDisplay;

        $data[$position][$last_first_name] = [
          'label_name' => $customer->CommitteeMemberLabelName,
          'end_date' => $this->formatDate($customer->EndDate, 'Y'),
          'member_id' => $member_id,
          'company_id' => $customer->RepresentingMasterCustomer,
          'company_name' => $customer->RepresentingLabelName,
        ];
        if ($job_title = $this->getMemberJobTitle($member_id, $member_sub_id)) {
          $data[$position][$last_first_name]['job_title'] = $job_title;
        }
        $this->sort($data);
      }
    }
    else {
      $this->ssoHelper->log("Error with request to get Data Service Committee Members.");
    }
    return $data;
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
  protected function getMemberJobTitle($member_id, $member_sub_id) {
    $job_title = '';
    // Example path: CustomerInfos(MasterCustomerId='00094039',
    // SubCustomerId=0)/Addresses?$select=JobTitle .
    $path_element = "CustomerInfos(MasterCustomerId='" . $member_id .
      "',SubCustomerId=" . $member_sub_id . ")/Addresses";
    $query = [
      '$filter' => "AddressStatusCode eq 'GOOD'",
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
    $data = array();
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
