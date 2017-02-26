<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMISalesAgentApproveRejectForm.
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class PMMISalesAgentApproveRejectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sales_agent_approve_reject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    if (!$node || !($node instanceof \Drupal\node\Entity\Node)) {
      return [];
    }

    //
    $form['approve'] = [
      '#type' => 'submit',
      '#value' => $this->t('Approve a listing'),
    ];
    $form['reject'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reject a listing'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
