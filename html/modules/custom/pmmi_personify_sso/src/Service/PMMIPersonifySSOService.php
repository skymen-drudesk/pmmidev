<?php

namespace Drupal\pmmi_personify_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\SimpleXml;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use phpseclib\Crypt\Rijndael;

/**
 * Class PMMIPersonifySSOService.
 *
 * @package Drupal\pmmi_personify_sso
 */
class PMMIPersonifySSOService {

  use StringTranslationTrait;

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
   * Array represent current service setting.
   *
   * @var array
   */
  protected $ssoSettings;

  /**
   * Array represent current service setting for Crypt methods.
   *
   * @var array
   */
  protected $ssoAuth;

  /**
   * Pure-PHP implementation of Rijndael.
   *
   * @var \phpseclib\Crypt\Rijndael
   */
  protected $cipher;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor for Personify SSO Service.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $loggerFactory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $loggerFactory->get('pmmi_personify_sso');
    $this->ssoSettings = static::reformatConfig($config_factory->get('pmmi_personify_sso.settings'));
    $this->ssoAuth = static::reformatConfig($config_factory->get('pmmi_personify_sso.settings'), TRUE);
    $this->cipher = new Rijndael();
    $this->cipher->setBlockLength(128);
    $this->cipher->setKeyLength(128);
    $this->cipher->setKey($this->ssoAuth['vp']);
    $this->cipher->setIV($this->ssoAuth['vib']);
  }

  /**
   * Returns a configuration array as used by the service.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   * @param bool $crypt
   *   Variable for return settings for Crypt methods.
   *
   * @return array
   *   The service configuration array.
   */
  protected static function reformatConfig(ImmutableConfig $config, $crypt = FALSE) {
    return array(
      'login_uri' => $config->get('login_uri'),
      'service_uri' => $config->get('service_uri'),
      'vi' => $config->get('vi'),
      'vu' => $config->get('vu'),
      'vp' => $crypt ? hex2bin($config->get('vp')) : $config->get('vp'),
      'vib' => $crypt ? hex2bin($config->get('vib')) : $config->get('vib'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getCipher() {

  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($string) {
    return bin2hex($this->cipher->encrypt($string));
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($string) {
    return $this->cipher->decrypt(hex2bin($string));
  }

  /**
   * {@inheritdoc}
   */
  public function request($request_array) {
    $uri = $this->ssoSettings['service_uri'] . '/' . $request_array['path'];
    $body = $request_array['body'];
    try {
      $response = $this->httpClient->post($uri, $body);
      $data = $response->getBody();
    } catch (RequestException $e) {
      $this->logger->warning('Failed to send request to the Personify SSO Service "%error".', array('%error' => $e->getMessage()));
      // @todo Only for debuging. Disable on production.
      drupal_set_message($this->t('Failed to send request to the Personify SSO Service  "%error".', array('%error' => $e->getMessage())));
      $data = [];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function tokenIsValid($token) {
    $raw_data = $this->decrypt($token);
    if ($raw_data) {
      $data = explode('|', $raw_data);
      $request_array = [
        'path' => '',
        'body' => [
          'vendorUsername' => $this->ssoSettings['vu'],
          'vendorPassword' => $this->ssoSettings['vp'],
          'customerToken' => $data[1],
        ],
      ];
      $response = $this->request($request_array);

    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseXML($xml) {
    $document = new \DOMDocument();

    // Load the RSS, if there are parsing errors, abort and return the unchanged
    // markup.
    $previous_value = libxml_use_internal_errors(TRUE);
    $document->loadXML($xml);
    $errors = libxml_get_errors();
    libxml_use_internal_errors($previous_value);
    if ($errors) {
      return '';
    }
    $xmlPath = new \DomXpath($document);
    $xmlPath->registerNamespace('m', $document->getElementsByTagName("Schema")->item(0)->getAttribute('xmlns'));

  }

}
