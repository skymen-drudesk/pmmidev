<?php

namespace Drupal\audience_select\Plugin\Block;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Image\ImageFactory;

/**
 * Provides a 'AudienceBlock' block.
 *
 * @Block(
 *  id = "audience_block",
 *  admin_label = @Translation("Audience block"),
 * )
 */
class AudienceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\audience_select\Service\AudienceManager definition.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AudienceManager $audience_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->AudienceManager = $audience_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('audience_select.audience_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'audience_id' => '',
        'image_style' => 'gateway_style',
        'audience_overrides' => array(),
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $overrides = $this->configuration['audience_overrides'];
    $styleselect = [];
    foreach (ImageStyle::loadMultiple() as $style) {
      $styleselect[$style->id()] = $style->label();
    }
    $form['audience_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#description' => $this->t('Select Auidience'),
      '#default_value' => $this->configuration['audience_id'],
      '#options' => $this->AudienceManager->getOptionsList(),
      '#required' => TRUE,
//      '#maxlength' => 64,
//      '#size' => 64,
      '#weight' => '1',
    ];
    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Audience Image style'),
      '#description' => $this->t('Select Auidience Image style'),
      '#default_value' => $this->configuration['image_style'],
      '#options' => $styleselect,
      '#required' => TRUE,
//      '#maxlength' => 64,
//      '#size' => 64,
      '#weight' => '2',
    ];
    // Overrides defaults.
    $form['overrides'] = array(
      '#type' => 'details',
      '#title' => $this->t('Overrides defaults'),
      '#open' => !empty($overrides),
      '#tree' => TRUE,
      '#weight' => '2',
    );
    $form['overrides']['audience_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audience Title Override'),
      '#description' => $this->t('Override default Audience Title'),
      '#default_value' => array_key_exists('audience_title', $overrides) ? $overrides['audience_title'] : NULL,
      '#maxlength' => 40,
      '#size' => 40,
      '#weight' => '0',
    ];
    if (array_key_exists('audience_redirect_url', $overrides)) {
      $default_url = $this->AudienceManager->getUriAsDisplayableString($overrides['audience_redirect_url']);
    }
    else {
      $default_url = NULL;
    }
    $form['overrides']['audience_redirect_url'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Audience Redirect Url Override'),
      '#description' => $this->t('Referenced to node. Manually entered paths should start with /, ? or #.'),
      '#default_value' => $default_url,
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
      '#maxlength' => 200,
      '#size' => 40,
      '#weight' => '1',
    ];
    $form['overrides']['audience_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Audience Image Override'),
      '#description' => $this->t('Override default background image for Audience'),
      '#default_value' => array_key_exists('audience_image', $overrides) ? $overrides['audience_image'] : NULL,
      '#upload_location' => 'public://audience/image/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('png jpg jpeg'),
      ),
      '#weight' => '2',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['audience_id'] = $form_state->getValue('audience_id');
    $this->configuration['image_style'] = $form_state->getValue('image_style');
    $overrides = $form_state->getValue('overrides');
    $result_overrides = array_filter($overrides);
    if (!empty($result_overrides)) {
      $this->setConfigurationValue('audience_overrides', $result_overrides);
    }
    else {
      $this->setConfigurationValue('audience_overrides', array());
    }
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    LinkWidget::validateUriElement($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $audience_id = $this->configuration['audience_id'];
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    $image_style = ImageStyle::load($this->configuration['image_style']);
    $audience = AudienceManager::load($audience_id);
    $overrides = $this->configuration['audience_overrides'];
    if (!empty($overrides)) {
      foreach ($overrides as $key => $value) {
        $audience[$key] = $value;
      }
    }
    $image_url = '';
    if(array_key_exists('audience_image', $audience)){
      if(!empty($audience['audience_image'])){
        $image = File::load($audience['audience_image'][0]);
        $image_style_uri = $image_style->buildUri($image->getFileUri());
        $status = TRUE;
        if (!file_exists($image_style_uri)) {
          $status = $image_style->createDerivative($image->getFileUri(), $image_style_uri);
        }
        $image_uri = $status ? $image_style_uri : $image->getFileUri();
        $image_url = file_url_transform_relative(file_create_url($image_uri));
      }
    }

    $options = array(
      'query' => array('audience' => $audience_id)
    );
    $url = Url::fromUri($audience['audience_redirect_url'], $options);
    $build['#theme'] = 'audience_select_block';
    $build['#audience_title'] = $audience['audience_title'];
    $build['#audience_image'] = $image_url;
    $build['#audience_redirect_url'] = $url;

    return $build;
  }


}
