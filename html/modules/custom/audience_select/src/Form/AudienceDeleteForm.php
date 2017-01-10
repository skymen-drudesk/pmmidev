<?php

namespace Drupal\audience_select\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deleting a Audience.
 */
class AudienceDeleteForm extends ConfirmFormBase {
  use ConfigFormBaseTrait;

  /**
   * The Audience ID.
   *
   * @var string
   */
  protected $audience_id;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['audience_select.settings'];
  }


  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %audience_id?', array('%audience_id' => $this->audience_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('audience_select.audience_settings_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audience_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $audience_id = NULL) {
    $this->audience_id = $audience_id;

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('audience_select.settings')
      ->clear('map.' . $this->audience_id)
      ->save();

    $args = array(
      '%audience_id' => $this->audience_id,
    );

    $this->logger('Audience')->notice('The %audience_id browser language code has been deleted.', $args);

    drupal_set_message($this->t('The %audience_id browser language code has been deleted.', $args));

    $form_state->setRedirect('audience_select.audience_settings_form');
  }

}
