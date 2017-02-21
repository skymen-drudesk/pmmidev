<?php

namespace Drupal\pmmi_personify_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use GuzzleHttp\Client;
use phpseclib\Crypt\Base;
use phpseclib\Crypt\Rijndael;

/**
 * Class PMMIPersonifySSOService.
 *
 * @package Drupal\pmmi_personify_sso
 */
class PMMIPersonifySSOService {

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
  protected $ssoAuth;

  /**
   * Pure-PHP implementation of Rijndael.
   *
   * @var \phpseclib\Crypt\Rijndael
   */
  protected $cipher;

  /**
   * Constructor for Personify SSO Service.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->ssoAuth = static::reformatConfig($config_factory->get('pmmi_personify_sso.settings'));
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
   *
   * @return array
   *   The service configuration array.
   */
  protected static function reformatConfig(ImmutableConfig $config) {
    return array(
      'login_uri' => $config->get('login_uri'),
      'service_uri' => $config->get('service_uri'),
      'vi' => $config->get('vi'),
      'vu' => $config->get('vu'),
      'vp' => hex2bin($config->get('vp')),
      'vib' => hex2bin($config->get('vib')),
    );
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

}
