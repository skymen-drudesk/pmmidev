<?php

/**
 * @file
 * Contains \Drupal\audience_select\Plugin\Block\AudienceSwitcherBlock.
 */

namespace Drupal\audience_select\Plugin\Block;

use Drupal\audience_select\Controller\AudienceSelectController;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block;

/**
 * Provides a 'Audience Switcher' block.
 *
 * @Block(
 *   id = "audience_switcher",
 *   admin_label = @Translation("Audience Switcher")
 * )
 */
class AudienceSwitcherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $audiences = AudienceSelectController::getKeyedAudiences();
    return array(
      '#theme' => 'audience_switcher_block',
      '#audiences' => $audiences,
    );
  }

}
