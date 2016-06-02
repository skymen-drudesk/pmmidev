<?php

namespace Drupal\system\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\audience_select\Controller\AudienceSelectController;

/**
 * Provides a 'Current Theme' condition.
 *
 * @Condition(
 *   id = "current_audience",
 *   label = @Translation("Current Audience"),
 * )
 */
class CurrentAudienceCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a CurrentAudienceCondition condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('settings' => '') + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $audiences = $audiences = AudienceSelectController::getKeyedAudiences();
    $options = [];
    foreach ($audiences as $key => $audience) {
      $options[$key] = $audience['gateway'];
    }

    $form['audience'] = array(
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#default_value' => $this->configuration['settings'],
      '#options' => $options,
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['audience'] = $form_state->getValue('audience');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $cookie = $_COOKIE;
    var_dump($cookie);

    if (!$this->configuration['settings']) {
      return TRUE;
    }

    return $cookie;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->isNegated()) {
      return $this->t('The current audience is not @audience', array('@audience' => $this->configuration['settings']));
    }

    return $this->t('The current audience is @audience', array('@audience' => $this->configuration['settings']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'audience';
    return $contexts;
  }

}
