<?php

namespace Drupal\audience_select\Cache\Context;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the AudienceCacheContext service, for "per Audience" caching.
 *
 * Cache context ID: 'audience'
 */
class AudienceCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * The configured audiences.
   *
   * @var null
   */
  protected $audience;

  /**
   * The plugin implementation definition.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *
   *   The request stack.
   *
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   */
  public function __construct(RequestStack $request_stack, AudienceManager $audience_manager) {
    parent::__construct($request_stack);
    $this->AudienceManager = $audience_manager;
    $this->audience = $audience_manager->getCurrentAudience();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Audience');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $audience = $this->audience;
//    return $audience;
    return $this->requestStack->getCurrentRequest()->cookies->get('audience_select_audience');
//    if ($audience === NULL) {
//      return $this->requestStack->getCurrentRequest()->cookies->all();
//    }
//    else {
//      return $this->requestStack->getCurrentRequest()->cookies->get($audience);
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
