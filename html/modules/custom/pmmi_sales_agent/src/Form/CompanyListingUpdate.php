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

    if ($form_state->getValue('nid')) {
      // If user requested one-time update link and mail exists - show
      // successful message.
      if (!empty($form_state->mail) && $ms->get('one_time_alert')) {
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
      // If user requested one-time update link and mail is not exist - show
      // fail message.
      elseif (empty($form_state->mail) && $ms->get('one_time_wrong_mail_alert')) {
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($form_state->getValue('nid'));

        $form['warning'] = [
          '#markup' => \Drupal::token()->replace($ms->get('one_time_wrong_mail_alert_message'), ['node' => $node]),
          '#prefix' => '<div class="one-time-company-update-message">',
          '#suffix' => '</div>',
        ];
        return $form;
      }
    }

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
      $mail = $node->get('field_primary_contact_email')->getValue();
      $form_state->mail = $mail;
      $form_state->setRebuild();

      if (!$form_state->mail) {
        return;
      }

      // Send one-time update link to the primary contact email.
      $mail = $mail[0]['value'];
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

        try {
          $result = \Drupal::service('plugin.manager.mail')
            ->mail('pmmi_sales_agent', 'pmmi_one_time_update', $mail, $langcode, $params, TRUE);
          if (!empty($result['result']) && $result['result'] === TRUE) {
            \Drupal::logger('pmmi_sales_agent')->info('Message successfully sent. Message details: <br> To: @to<br>From: @from<br>Subject: @subject<br>Body: @body',
              [
                '@to' => !empty($result['to']) ? $result['to'] : 'empty',
                '@from' => !empty($result['from']) ? $result['from'] : 'empty',
                '@subject' => !empty($result['subject']) ? $result['subject'] : 'empty' ,
                '@body' => !empty($result['body']) ? $result['body'] : 'empty'
              ]
            );
          }
          else {
            \Drupal::logger('pmmi_sales_agent')->error('Message didn\'t send.  Message details: <br>To: @to<br>From: @from<br>Subject: @subject<br>Body: @body',
              [
                '@to' => !empty($result['to']) ? $result['to'] : 'empty',
                '@from' => !empty($result['from']) ? $result['from'] : 'empty',
                '@subject' => !empty($result['subject']) ? $result['subject'] : 'empty' ,
                '@body' => !empty($result['body']) ? $result['body'] : 'empty'
              ]
            );
          }
        } catch (Exception $e) {
          \Drupal::logger('pmmi_sales_agent')->error('Message didn\'t send. <br>The exception code is : @code<br> Message: @exception',
            [
              '@code' => $e->getCode(),
              '@exception' => $e->getMessage()
            ]
          );
        }
        $form_state->setRebuild();
      }
    }
  }
}
