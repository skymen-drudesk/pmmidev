<?php

namespace Drupal\audience_select\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current audience as a context.
 */
class CurrentAudienceContext implements ContextProviderInterface {

  use StringTranslationTrait;


  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $cookie = $_COOKIE;
    var_dump($cookie);

    $context = new Context(new ContextDefinition('cookie:audience', $this->t('Audience cookie')), $cookie);
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
