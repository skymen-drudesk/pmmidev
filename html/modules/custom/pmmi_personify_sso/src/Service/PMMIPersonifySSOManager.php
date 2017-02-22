<?php

namespace Drupal\pmmi_personify_sso\Service;

use Drupal\externalauth\ExternalAuth;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class PMMIPersonifySSOManager.
 *
 * @package Drupal\pmmi_personify_sso
 */
class PMMIPersonifySSOManager {

  /**
   * Drupal\externalauth\ExternalAuth definition.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalauthExternalauth;
  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannelExternalauth;

  /**
   * Drupal\pmmi_personify_sso\Service\PMMIPersonifySSOService service.
   *
   * @var \Drupal\pmmi_personify_sso\Service\PMMIPersonifySSOService
   */
  protected $ssoService;

  /**
   * Constructor.
   */
  public function __construct(
    ExternalAuth $externalauth_externalauth,
    ConfigFactory $config_factory,
    EntityTypeManager $entity_type_manager,
    LoggerChannel $logger_channel_externalauth,
    PMMIPersonifySSOService $sso_service
  ) {
    $this->externalauthExternalauth = $externalauth_externalauth;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerChannelExternalauth = $logger_channel_externalauth;
    $this->ssoService = $sso_service;
  }

  /**
   * {@inheritdoc}
   */
  public function login() {

  }

  /**
   * {@inheritdoc}
   */
  public function logout() {

  }
}
