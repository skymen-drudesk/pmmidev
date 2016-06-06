<?php

/**
 * @TODO: Make this actually work! Does a context actually have to be an entity
 *   (e.g. a config entity)? Is a context necessary to have a condition plugin?
 *   Does it actually make sense to provide this context?
 */

namespace Drupal\audience_select\ContextProvider;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\Context\ContextProviderInterface;

/**
 * Sets the current audience as a context.
 *
 * @package Drupal\audience_select\ContextProvider
 */
class CurrentAudienceContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $audience_manager;

  /**
   * The current audience.
   *
   * @var null
   */
  protected $audience;

  /**
   * Constructs a new CurrentAudienceContext.
   *
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   *   The audience manager.
   */
  public function __construct(AudienceManager $audience_manager) {
    $this->audience_manager = $audience_manager;
    $this->audience = $this->audience_manager->getCurrentAudience();
  }


  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    ddl('in CurrentAudienceContext getRuntimeContexts().');

    $context = new Context(new ContextDefinition('audience',
      $this->t('Current audience'),
      FALSE
    ),
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
    return $this->getRuntimeContexts([]);
  }

}
