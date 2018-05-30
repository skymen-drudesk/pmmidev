<?php

namespace Drupal\queue_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QueueUIInspectForm
 * @package Drupal\queue_ui\Form
 */
class QueueUIInspectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'queue_ui_inspect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queue_name = FALSE) {
    if ($queue = _queue_ui_queueclass($queue_name)) {
      return $queue->inspect($queue_name);
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
