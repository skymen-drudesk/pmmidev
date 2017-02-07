<?php

namespace Drupal\audience_select;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the http_middleware.page_cache service.
 *
 * Overrides the http_middleware.page_cache service
 * to enable caching for anonymous.
 */
class AudienceSelectServiceProvider extends ServiceProviderBase {

  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('http_middleware.page_cache');
    $definition->setClass('Drupal\audience_select\StackMiddleware\AudiencePageCache');
  }

}
