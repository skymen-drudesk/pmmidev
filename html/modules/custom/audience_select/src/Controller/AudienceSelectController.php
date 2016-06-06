<?php

/**
 * @file
 * Contains \Drupal\audience_select\Controller\AudienceSelectController
 */

namespace Drupal\audience_select\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\audience_select\Service\AudienceManager;

/**
 * Controller routines for Audience select pages.
 */
class AudienceSelectController extends ControllerBase {

  private $audience_manager;

  public function __construct(AudienceManager $audience_manager)
  {
    $this->audience_manager = $audience_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('audience_select.audience_manager')
    );
  }
  
  public function content() {
    $audiences = $this->audience_manager->getAllAudiences();
    return [
      '#theme' => 'page__gateway',
      '#audiences' => $audiences,
    ];
  }

}
