<?php

namespace Drupal\pmmi_personify_sso\PageCache;

use Drupal\pmmi_personify_sso\Service\PMMIPersonifySSOHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures pages configured with gateway authentication are not cached.
 *
 * The logic we use to determine if a user should be redirected to gateway auth
 * is currently not compatible with page caching.
 */
class DenySSO implements ResponsePolicyInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Condition manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionManager;

  /**
   * Constructs a response policy for disabling cache on specific Personify SSO paths.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExecutableManagerInterface $condition_manager) {
    $this->configFactory = $config_factory;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    $config = $this->configFactory->get('pmmi_personify_sso.settings');
    if ($config->get('gateway.check_frequency') !== PMMIPersonifySSOHelper::CHECK_NEVER) {
      // User can indicate specific paths to enable (or disable) gateway mode.
      $condition = $this->conditionManager->createInstance('request_path');
      $condition->setConfiguration($config->get('gateway.paths'));
      if ($this->conditionManager->execute($condition)) {
        return static::DENY;
      }
    }
    return NULL;
  }

}
