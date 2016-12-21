<?php

/**
 * @file
 * Contains \Drupal\audience_select\Plugin\Block\AudienceSelectBlock.
 */

namespace Drupal\audience_select\Plugin\Block;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block;

/**
 * Provides a 'Audience Select' block.
 *
 * @Block(
 *   id = "audience_select",
 *   admin_label = @Translation("Audience Selector")
 * )
 */
class AudienceSelectBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $audience_manager = new AudienceManager();
    $audiences = $audience_manager->getGatewayAudiences();
    return array(
      '#theme' => 'audience_select_block',
      '#audiences' => $audiences,
    );
  }

}
