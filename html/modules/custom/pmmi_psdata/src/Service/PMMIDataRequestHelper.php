<?php

namespace Drupal\pmmi_psdata\Service;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class PMMIDataRequestHelper.
 *
 * @package Drupal\pmmi_psdata
 */
class PMMIDataRequestHelper {

  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  public $ssoHelper;

  /**
   * Drupal date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * PMMIDataRequestHelper constructor.
   *
   * @param ClientInterface $http_client
   *   The HTTP Client library.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param DateFormatterInterface $date_formatter
   *   Drupal date formatter service.
   */
  public function __construct(
    ClientInterface $http_client,
    PMMISSOHelper $sso_helper,
    DateFormatterInterface $date_formatter
  ) {
    $this->httpClient = $http_client;
    $this->ssoHelper = $sso_helper;
    $this->dateFormatter = $date_formatter;
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
  public function buildGetRequest($collection, array $query) {
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
  public function handleAsyncRequests(array $requests) {
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
  public function handleRequest(array $request_param) {
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
  public function addFilter($operator, $property, array $values, $first = FALSE, $wrap = FALSE) {
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
  public function formatDate($json_date, $format) {
    $timestamp = preg_replace('/[^0-9]/', '', $json_date);
    return $this->dateFormatter->format($timestamp / 1000, 'custom', $format, 'UTC');
  }

}
