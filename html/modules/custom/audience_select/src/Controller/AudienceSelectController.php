<?php

/**
 * @file
 * Contains \Drupal\audience_select\Controller\AudienceSelectController
 */

namespace Drupal\audience_select\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\audience_select\Service\AudienceManager;

/**
 * Controller routines for Audience select pages.
 */
class AudienceSelectController implements ContainerInjectionInterface {

  private $audience_manager;

  public function __construct(AudienceManager $audience_manager)
  {
    $this->audience_manager = $audience_manager;
  }

  public static function create(ContainerInterface $container)
  {
    return new self($container->get('audience_select.audience_manager'));
  }
  
  public function content() {
    $audiences = $this->audience_manager->getAllAudiences();
    return [
      '#theme' => 'page__gateway',
      '#audiences' => $audiences,
    ];
  }

}
