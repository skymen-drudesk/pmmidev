<?php

namespace Drupal\audience_select\Form;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Unish\archiveDumpCase;

/**
 * Class AudienceSettingsForm.
 *
 * @package Drupal\audience_select\Form
 */
class AudienceSettingsForm extends ConfigFormBase {

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $audience_manager;

  /**
   * The configured audiences.
   *
   * @var null
   */
  protected $audiences;

  /**
   * {@inheritdoc}
   *
   *   The plugin implementation definition.
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, AudienceManager $audience_manager) {
    parent::__construct($config_factory);
    $this->audience_manager = $audience_manager;
    $this->audiences = $audience_manager->getData();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('audience_select.audience_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'audience_select.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audience_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
//    $config = $this->config('audience_select.settings');
//    $gateway_url = $config->get('gateway_url');
    $form['gateway_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gateway Url'),
      '#description' => $this->t('Paths should start with /, ? or #.'),
      '#default_value' => $this->audience_manager->getGateway(),
      '#element_validate' => array(
        array(
          get_called_class(),
          'validateUriElement'
        )
      ),
    ];

    $form['audiences'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Audience ID'),
        $this->t('Audience Title'),
        $this->t('Audience Redirect Url'),
        $this->t('Audience Image'),
        $this->t('Audience Operations'),
      ],
      '#attributes' => ['id' => 'audience-table'],
      '#empty' => $this->t('No audiences available.'),
    ];
    $audiences = $this->audiences;

      foreach ($audiences as $audience_id => $audience) {
      $form['audiences'][$audience_id] = array(
        'audience_id' => array(
          '#title' => $this->t('Audience ID'),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#disabled' => TRUE,
          '#default_value' => $audience_id,
          '#size' => 20,
          '#required' => TRUE,
        ),
        'audience_title' => array(
          '#title' => $this->t('Audience Title'),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#default_value' => $audience['audience_title'],
          '#required' => TRUE,
        ),
        'audience_redirect_url' => array(
          '#title' => $this->t('Audience Redirect Url'),
          '#title_display' => 'invisible',
          '#type' => 'entity_autocomplete',
          '#target_type' => 'node',
          '#size' => 20,
          '#default_value' => $audience['audience_redirect_url'],
          '#placeholder' => $this->t(''),
          '#maxlength' => 200,
          '#attributes' => array(
            'data-autocomplete-first-character-blacklist' => '/#?'
          ),
          '#element_validate' => array(
            array(
              get_called_class(),
              'validateUriElement'
            )
          ),
          '#process_default_value' => FALSE,
          '#field_prefix' => rtrim(Url::fromRoute('<front>', array(), array('absolute' => TRUE))
            ->toString(), '/')
        ),
        'audience_image' => array(
          '#type' => 'managed_file',
          '#title' => $this->t('Image'),
          '#title_display' => 'invisible',
          '#required' => TRUE,
          '#upload_location' => 'public://audience/image/',
          '#default_value' => (!empty($audience['audience_image'])) ? $audience['audience_image'] : NULL,
          '#upload_validators' => array(
            'file_validate_extensions' => array('png jpg jpeg'),
          ),
        ),
      );
      // Operations column.
      $form['audiences'][$audience_id]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $form['audiences'][$audience_id]['operations']['#links']['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('audience_select.audience_settings_delete_form', ['audience_id' => $audience_id]),
      ];
    }

    // Add empty row.
    $form['new_audience'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add a new Audience'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['new_audience']['audience_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Audience Title'),
      '#size' => 40,
    );
    $form['new_audience']['audience_id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Audience ID'),
      '#maxlength' => 20,
      '#required' => FALSE,
      '#machine_name' => array(
        'exists' => ['Drupal\audience_select\Service\AudienceManager', 'load'],
        'label' => $this->t('Audience ID'),
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => array('new_audience', 'audience_title'),
      ),
      '#description' => t('A unique machine-readable name for this Audience. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL, in which underscores will be converted into hyphens.'),
      '#states' => array(
        'required' => array(
          ':input[name="new_audience[audience_title]"]' => array('filled' => TRUE),
        ),
      )
    );
    $form['new_audience']['audience_redirect_url'] = array(
      '#title' => $this->t('Audience Redirect Url'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#description' => $this->t('Referenced to node. Manually entered paths should start with /, ? or #.'),
      '#size' => 30,
      '#placeholder' => $this->t(''),
      '#maxlength' => 60,
      '#attributes' => array(
        'data-autocomplete-first-character-blacklist' => '/#?'
      ),
      '#element_validate' => array(array(get_called_class(), 'validateUriElement')),
      '#process_default_value' => FALSE,
      '#states' => array(
        'required' => array(
          ':input[name="new_audience[audience_title]"]' => array('filled' => TRUE),
        ),
      ),
      '#field_prefix' => rtrim(\Drupal::url('<front>', array(), array('absolute' => TRUE)), '/')
    );
    $form['new_audience']['audience_image'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Audience Image'),
      '#description' => $this->t('Default background image for Audience block'),
      '#upload_location' => 'public://audience/image/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('png jpg jpeg'),
      ),
      '#states' => array(
        'required' => array(
          ':input[name="new_audience[audience_title]"]' => array('filled' => TRUE),
        ),
      )
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $unique_values = array();

    // Check all mappings.
    if ($form_state->hasValue('audiences')) {
      $audiences = $form_state->getValue('audiences');
      foreach ($audiences as $key => $data) {
        $unique_values[$data['audience_id']]['audience_title'] = $data['audience_title'];
        $unique_values[$data['audience_id']]['audience_redirect_url'] = $data['audience_redirect_url'];
        $unique_values[$data['audience_id']]['audience_image'] = $data['audience_image'];
      }
    }

    // Check new audience.
    $data = $form_state->getValue('new_audience');
    if (!empty($data['audience_id'])) {
      foreach ($data as $key => $value) {
        if ($key == 'audience_id' && array_key_exists($value, $unique_values)) {
          $form_state->setErrorByName('audiences][' . $key . '][audience_id', $this->t('Audience ID must be unique.'));
        }
        elseif (empty($value)) {
          $form_state->setErrorByName('new_audience][' . $key, $this->t('This field is required.'));
        }
        else {
          $temp_value[$key] = $value;
        }
      }
      $unique_values[$data['audience_id']] = $temp_value;
    }

    $form_state->set('audiences', $unique_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->get('audiences');
    $config = $this->config('audience_select.settings');
    if (!empty($mappings)) {
      $config->setData(['map' => $mappings]);
    }
    $config->set('gateway_url', $form_state->getValue('gateway_url'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    LinkWidget::validateUriElement($element, $form_state, $form);
  }

}
