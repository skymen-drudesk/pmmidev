<?php

namespace Drupal\pmmi_sso\Service;

use DateTime;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class PMMISSOHelper.
 */
class PMMISSOHelper {

  /**
   * Gateway config: never check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_NEVER = -2;

  /**
   * Gateway config: check once per session to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ONCE = -1;

  /**
   * Gateway config: check on every page load to see if the user is logged in.
   *
   * @var int
   */
  const CHECK_ALWAYS = 0;

  /**
   * Token config: TokenTTL check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const TOKEN_DISABLED = 0;

  /**
   * Token config: TokenTTL check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const TOKEN_TTL = 1;

  /**
   * Token config: TokenTTL check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const TOKEN_ACTION_LOGOUT = 0;

  /**
   * Token config: TokenTTL check preemptively to see if the user is logged in.
   *
   * @var int
   */
  const TOKEN_ACTION_FORCE_LOGIN = 1;

  /**
   * Event type identifier for the PMMISSOPreUserLoadEvent.
   *
   * @var string
   */
  const EVENT_PRE_USER_LOAD = 'pmmi_sso.pre_user_load';

  /**
   * Event type identifier for the PMMISSOPreRegisterEvent.
   *
   * @var string
   */
  const EVENT_PRE_REGISTER = 'pmmi_sso.pre_register';

  /**
   * Event type identifier for the PMMISSOPreLoginEvent.
   *
   * @var string
   */
  const EVENT_PRE_LOGIN = 'pmmi_sso.pre_login';

  /**
   * Event type identifier for pre auth events.
   *
   * @var string
   */
  const EVENT_PRE_REDIRECT = 'pmmi_sso.pre_redirect';

  /**
   * Event type identifier for the PMMISSOPreUserLoadEvent.
   *
   * @var string
   */
  const EVENT_DATA_SERVICE_LOAD = 'pmmi_sso.data_service_load';

  /**
   * Stores database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Used to encode/decode Token from Personify SSO Service.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOCrypt
   */
  protected $crypt;

  /**
   * Constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param Connection $database_connection
   *   The database service.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param SessionInterface $session
   *   The session handler.
   * @param PMMISSOCrypt $crypt
   *   The crypt handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    UrlGeneratorInterface $url_generator,
    Connection $database_connection,
    LoggerChannelFactoryInterface $logger_factory,
    SessionInterface $session,
    PMMISSOCrypt $crypt
  ) {
    $this->urlGenerator = $url_generator;
    $this->connection = $database_connection;
    $this->session = $session;
    $this->settings = $config_factory->get('pmmi_sso.settings');
    $this->loggerChannel = $logger_factory->get('pmmi_sso');
    $this->crypt = $crypt;
  }

  /**
   * Return the validation options used to validate the provided token.
   *
   * @param string $raw_token
   *   The token to validate.
   *
   * @return array
   *   The options for building validation Request.
   */
  public function getServerValidateOptions($raw_token, $internal) {
    $token_data = $this->crypt->decrypt($raw_token);
    $options['decrypt'] = FALSE;
    $token = '';
    if (is_string($token_data) || is_numeric($token_data)) {
      $options['decrypt'] = TRUE;
      if ($internal) {
        $token_search = \Drupal::entityTypeManager()
          ->getStorage('pmmi_sso_token')
          ->loadByProperties(['uid' => $token_data]);
        /** @var \Drupal\pmmi_sso\Entity\PMMISSOToken $token_entity */
        if ($token_entity = reset($token_search)) {
          $token = $token_entity->getToken();
        }
      }
      else {
        $data = explode('|', $token_data);
        $token = $data[1];
      }
    }
    $query = $this->buildSsoServiceQuery(
      'SSOCustomerTokenIsValid',
      ['vu', 'vp'],
      ['customerToken' => $token]
    );
    return $options + $query;
  }

  /**
   * Return the query array for building SSO Service Request.
   *
   * @param string $path
   *   The service path.
   * @param array $options
   *   The array of options to add to the query.
   * @param array $parameter
   *   The service parameter to add to query.
   *
   * @return array
   *   The options for building SSO Service Request.
   */
  public function buildSsoServiceQuery($path, array $options, array $parameter) {
    $service_url = $this->settings->get('service_uri') . '/' . $path;
    $query = array();
    $result = array();
    foreach ($options as $option) {
      switch ($option) {
        case 'vi':
          $query['vendorUsername'] = $this->getVu();
          break;

        case 'vu':
          $query['vendorUsername'] = $this->getVu();
          break;

        case 'vp':
          $query['vendorPassword'] = $this->getVp();
          break;

        case 'vib':
          $query['vendorBlock'] = $this->getVib();
          break;
      }
    }
    $result['uri'] = $service_url;
    $result['params'] = $query + $parameter;
    return $result;
  }

  /**
   * Return the query array for building Personify Data Service Request.
   *
   * @param string $collection
   *   The service collection argument.
   * @param array $query
   *   The service query array to build request query.
   *
   * @return array
   *   The options for building Data Service Request.
   */
  public function buildDataServiceQuery($collection, array $query) {
    $result = array();
    $query = UrlHelper::buildQuery($query);
    $service_url = $this->settings->get('data_service.endpoint') . '/' . $collection . '?' . $query;
    $auth_header = [
      'headers' => [
        'Accept' => 'application/json',
      ],
      'auth' => [
        $this->settings->get('data_service.username'),
        $this->settings->get('data_service.password'),
      ],
    ];
    $result['uri'] = $service_url;
    $result['params'] = $auth_header;
    return $result;
  }

  /**
   * Construct the base URL to the PMMI SSO server.
   *
   * @return string
   *   The base URL.
   */
  public function getServerBaseUrl() {
    $url = $this->settings->get('login_uri');
    return $url;
  }

  /**
   * Return the service URL with query string.
   *
   * @param array $service_params
   *   An array of query string parameters to append to the service URL.
   *
   * @return string
   *   The fully constructed service URL to use for PMMI SSO server.
   */
  public function generateSsoServiceUrl(array $service_params = []) {
    if (isset($service_params['returnto'])) {
      if (!empty($service_params['returnto'])) {
        $service_params['ue'] = base64_encode($service_params['returnto']);
      }
      unset($service_params['returnto']);
    }
    $service_params['cti'] = $this->crypt->encrypt($service_params['cti']);
    return $this->urlGenerator->generate('pmmi_sso.service', $service_params, FALSE);
  }

  /**
   * Generate the Login URI to the PMMI SSO server.
   *
   * @param string $return_uri
   *   The string with query parameters.
   *
   * @return string
   *   The login URI as string.
   */
  public function generateLoginUrl($return_uri) {
    // Encode URI where need return user after correct token validation.
    $uri_encoded = base64_encode($return_uri);

    $now = DateTime::createFromFormat('U.u', microtime(TRUE));
    $timestamp = $now->format('YmdHisv');
    // Generate final redirect string to encode in Token.
    $return_parameters = ['ue' => $uri_encoded];
    $return_uri_sso = $this->urlGenerator->generate('pmmi_sso.service', $return_parameters, TRUE);

    // Fill string to encode in the Token.
    $string = $timestamp . '|' . $return_uri_sso;
    $token = $this->crypt->encrypt($string);

    // Generate final login uri.
    $url = Url::fromUri($this->getServerBaseUrl());
    $url->setAbsolute(TRUE);
    $url->setOption('query', ['vi' => $this->getVi(), 'vt' => $token]);

    return $url->toString();
  }

  /**
   * Get the Service URL to the PMMI SSO server.
   *
   * @return string
   *   The service URL.
   */
  public function getServiceUrl() {
    $url = $this->settings->get('service_uri');
    return $url;
  }

  /**
   * Get the Vendor Identifier to the PMMI SSO server.
   *
   * @return string
   *   The Vendor Identifier.
   */
  public function getVi() {
    $vi = $this->settings->get('vi');
    return $vi;
  }

  /**
   * Get the Vendor username to the PMMI SSO server.
   *
   * @return string
   *   The Vendor username.
   */
  public function getVu() {
    $vu = $this->settings->get('vu');
    return $vu;
  }

  /**
   * Get the Vendor password (HEX) to the PMMI SSO server.
   *
   * @return string
   *   The Vendor password (HEX).
   */
  public function getVp() {
    $vp = $this->settings->get('vp');
    return $vp;
  }

  /**
   * Get the Vendor initilization block (HEX) to the PMMI SSO server.
   *
   * @return string
   *   The Vendor initilization block (HEX).
   */
  public function getVib() {
    return $this->settings->get('vib');
  }

  /**
   * Get the Vendor initilization block (HEX) to the PMMI SSO server.
   *
   * @return string
   *   The Vendor initilization block (HEX).
   */
  public function getTokenFrequency() {
    return $this->settings->get('gateway.token_frequency');
  }

  /**
   * Get the Vendor initilization block (HEX) to the PMMI SSO server.
   *
   * @return string
   *   The Vendor initilization block (HEX).
   */
  public function getTokenAction() {
    return $this->settings->get('gateway.token_action');
  }

  /**
   * Log information to the logger.
   *
   * Only log supplied information to the logger if module is configured to do
   * so, otherwise do nothing.
   *
   * @param string $message
   *   The message to log.
   */
  public function log($message) {
    if ($this->settings->get('advanced.debug_log') == TRUE) {
      $this->loggerChannel->log(RfcLogLevel::DEBUG, $message);
    }
  }

//  /**
//   * Return the logout URL for the PMMI SSO server.
//   *
//   * @param \Symfony\Component\HttpFoundation\Request $request
//   *   The current request, to provide base url context.
//   *
//   * @return string
//   *   The fully constructed server logout URL.
//   */
//  public function getServerLogoutUrl(Request $request) {
//    $base_url = $this->getServerBaseUrl() . 'logout';
//    if ($this->settings->get('logout.logout_destination') != '') {
//      $destination = $this->settings->get('logout.logout_destination');
//      if ($destination == '<front>') {
//        // If we have '<front>', resolve the path.
//        $return_url = $this->urlGenerator->generate($destination, array(), TRUE);
//      }
//      elseif ($this->isExternal($destination)) {
//        // If we have an absolute URL, use that.
//        $return_url = $destination;
//      }
//      else {
//        // This is a regular Drupal path.
//        $return_url = $request->getSchemeAndHttpHost() . '/' . ltrim($destination, '/');
//      }
//
//      // PMMI SSO 2.0 uses 'url' param, while newer versions use 'service'.
//      if ($this->getSsoProtocolVersion() == '2.0') {
//        $params['url'] = $return_url;
//      }
//      else {
//        $params['service'] = $return_url;
//      }
//
//      return $base_url . '?' . UrlHelper::buildQuery($params);
//    }
//    else {
//      return $base_url;
//    }
//  }

  /**
   * Encapsulate UrlHelper::isExternal.
   *
   * @param string $url
   *   The url to evaluate.
   *
   * @return bool
   *   Whether or not the url points to an external location.
   *
   * @codeCoverageIgnore
   */
  protected function isExternal($url) {
    return UrlHelper::isExternal($url);
  }

//  /**
//   * Check if the current logout request should be served by ssologout.
//   *
//   * @param \Symfony\Component\HttpFoundation\Request $request
//   *   The request instance.
//   *
//   * @return bool
//   *   Whether to process logout as ssologout.
//   */
//  public function provideSsoLogoutOverride(Request $request) {
//    if ($this->settings->get('logout.pmmi_sso_logout') == TRUE) {
//      if ($this->isSsoSession($request->getSession()->getId())) {
//        return TRUE;
//      }
//    }
//
//    return FALSE;
//  }
//
//  /**
//   * Check if the given session ID was authenticated with PMMI SSO.
//   *
//   * @param string $session_id
//   *   The session ID to look up.
//   *
//   * @return bool
//   *   Whether or not this session was authenticated with PMMI SSO.
//   *
//   * @codeCoverageIgnore
//   */
//  public function isSsoSession($session_id) {
//    $results = $this->connection->select('pmmi_sso_login_data')
//      ->fields('pmmi_sso_login_data', array('sid'))
//      ->condition('sid', Crypt::hashBase64($session_id))
//      ->execute()
//      ->fetchAll();
//
//    return !empty($results);
//  }
//
//  /**
//   * Whether or not session IDs are stored for single logout.
//   *
//   * @return bool
//   *   Whether or not single logout is enabled in the configuration.
//   */
//  public function getSingleLogOut() {
//    return $this->settings->get('logout.enable_single_logout');
//  }

  /**
   * The amount of time to allow a connection to the PMMI SSO server to take.
   *
   * @return int
   *   The timeout in seconds.
   */
  public function getConnectionTimeout() {
    return $this->settings->get('advanced.connection_timeout');
  }

}
