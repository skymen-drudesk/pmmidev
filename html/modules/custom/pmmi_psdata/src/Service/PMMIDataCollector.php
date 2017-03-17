<?php

namespace Drupal\pmmi_psdata\Service;

use Drupal\pmmi_sso\Service\PMMISSOHelper;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactory;
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
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Cache\DatabaseBackend definition.
   *
   * @var \Drupal\Core\Cache\DatabaseBackend
   */
  protected $cacheDefault;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * Constructor.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param Client $http_client
   *   The HTTP Client library.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   */
  public function __construct(
    Client $http_client,
    ConfigFactory $config_factory,
    DatabaseBackend $cache_default,
    PMMISSOHelper $sso_helper
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->cacheDefault = $cache_default;
    $this->ssoHelper = $sso_helper;
  }


  /**
   * Get User Data that just registered via PMMI SSO.
   *
   * @param string $personify_id
   *   The Personify user ID.
   * @param User $account
   *   The user account.
   * @param array $user_data
   *   The array of last update timestamp.
   */
  public function getServiceData($page_type, $id) {

    $request_options = $this->ssoHelper->buildDataServiceQuery(
      'CustomerInfos',
      array(
        '$filter' => "MasterCustomerId eq '$personify_id'",
        '$select' => "LabelName, FirstName, LastName",
      )
    );
    $request_options['method'] = 'GET';
    $response = $this->handleRequest($request_options);
    if ($response instanceof RequestException) {
      $this->ssoHelper->log("Error with request to get User Data: " . $response->getMessage());
      return;
    }
    // Check if user data exist.
    if ($json_data = json_decode($response)) {
      $data = $json_data->d[0];
      // Parse and set user LabelName.
      if ($label_name = $data->LabelName) {
        $label_name == $account->getAccountName() ?: $account->set('name', $label_name);
      }
      else {
        $account->block();
        $this->ssoHelper->log('User name not exist or disabled.');
        return;
      }
      // Parse and set user FirstName.
      if ($first_name = $data->FirstName) {
        $first_name == $account->get('field_first_name') ?: $account->set('field_first_name', $first_name);
      }
      // Parse and set user LastName.
      if ($last_name = $data->LastName) {
        $last_name == $account->get('field_last_name') ?: $account->set('field_last_name', $last_name);
      }
      $user_data['info'] = REQUEST_TIME;
    }
    else {
      $this->ssoHelper->log('Invalid response from SSO Service.');
      return;
    }
  }

  /**
   * Attempt to handle request to PMMI Personify Services.
   *
   * @param array $request_param
   *   Parameters of the request.
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
    } catch (RequestException $e) {
      return $e;
    }
    return $response_data;
  }
}
