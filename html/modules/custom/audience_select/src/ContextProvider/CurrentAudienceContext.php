<?php

namespace Drupal\audience_select\ContextProvider;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current audience as a context.
 *
 * @package Drupal\audience_select\ContextProvider
 */
class CurrentAudienceContext extends AudienceManager {

  use StringTranslationTrait;

  /**
   * @var null
   */
  protected $audience;

  /**
   * CurrentAudienceContext constructor.
   */
  public function __construct() {
    $this->audience = $this->getCurrentAudience();
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts() {

    $context = new Context(new ContextDefinition('cookie:audience',
      $this->t('Current audience')),
      $this->audience);
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['audience']);
    $context->addCacheableDependency($cacheability);

    $result = [
      'current_audience' => $context,
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts();
  }

}
