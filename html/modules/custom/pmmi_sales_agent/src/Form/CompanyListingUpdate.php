<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for updating company listing.
 */
class CompanyListingUpdate extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'company_listing_update';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ms = \Drupal::config('pmmi_sales_agent.mail_settings');

    // If user requested one-time update link - show simple message.
    if ($form_state->getValue('nid') && $ms->get('one_time_alert')) {
      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($form_state->getValue('nid'));

      $form['success'] = [
        '#markup' => \Drupal::token()->replace($ms->get('one_time_alert_message'), ['node' => $node]),
        '#prefix' => '<div class="one-time-company-update-message">',
        '#suffix' => '</div>',
      ];
      return $form;
    }

    $form['nid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Please enter your company name'),
      '#required' => TRUE,
      '#target_type' => 'node',
      '#selection_handler' => 'default:node',
      '#selection_settings' => [
        'target_bundles' => [PMMI_SALES_AGENT_CONTENT],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($form_state->getValue('nid'));

    if ($node) {
      $mail = $node->get('field_primary_contact_email')->getValue()[0]['value'] ?: NULL;

      // Send one-time update link to the primary contact email.
      if ($mail && \Drupal::service('email.validator')->isValid($mail)) {
        $ms = \Drupal::config('pmmi_sales_agent.mail_settings');
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

        // Prepare a message and send it to the primary contact email.
        $params = [
          'subject' => $ms->get('one_time.subject'),
          'body' => $ms->get('one_time.body'),
          'from' => $ms->get('mail_notification_address'),
          'node' => $node,
        ];

        \Drupal::service('plugin.manager.mail')
          ->mail('pmmi_sales_agent', 'pmmi_one_time_update', $mail, $langcode, $params, TRUE);

        $form_state->setRebuild();
      }
      // @todo: what should we do if primary contact email is not set?
    }
  }
}
