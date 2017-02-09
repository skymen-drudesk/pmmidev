<?php

namespace Drupal\pmmi_personify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'PMMICommitteesBlock' block.
 *
 * @Block(
 *  id = "pmmi_committees_block",
 *  admin_label = @Translation("PMMI Committees block"),
 * )
 */
class PMMICommitteesBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'constituent_id' => $this->t(''),
        ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['constituent_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Constituent ID'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['constituent_id'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['constituent_id'] = $form_state->getValue('constituent_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['pmmi_committees_block_constituent_id']['#markup'] = '<p>' . $this->configuration['constituent_id'] . '</p>';

    return $build;
  }

}
