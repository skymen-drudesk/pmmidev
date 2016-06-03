<?php

namespace Drupal\audience_select\ContextProvider;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets the current audience as a context.
 *
 * @package Drupal\audience_select\ContextProvider
 */
class CurrentAudienceContext implements ContainerInjectionInterface {

  use StringTranslationTrait;

  private $audience_manager;

  protected $audience;

  public function __construct(AudienceManager $audience_manager)
  {
    $this->audience_manager = $audience_manager;
    $this->audience = $this->audience_manager->getCurrentAudience();
  }

  public static function create(ContainerInterface $container)
  {
    return new self($container->get('audience_select.audience_manager'));
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
