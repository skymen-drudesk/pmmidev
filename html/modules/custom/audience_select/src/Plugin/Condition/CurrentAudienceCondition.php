<?php

namespace Drupal\audience_select\Plugin\Condition;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'audience' condition to enable a condition based in module selected status.
 *
 * @Condition(
 *   id = "audience",
 *   label = @Translation("Audience"),
 * )
 *
 */
class CurrentAudienceCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface{

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
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('audience_select.audience_manager')
    );
  }
//*     "user_role" = @ContextDefinition("entity:user_role", label = @Translation("eee"))
//*     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
//*   context = {
//*     "user_role" = @ContextDefinition("entity:user_role", label = @Translation("eee"))
//*   }

  /**
   * Creates a new ExampleCondition instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AudienceManager $audience_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->audience_manager = $audience_manager;
    $this->audiences = $audience_manager->getData();
  }




// /**
//   * {@inheritdoc}
//   */
// public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//     // Sort all modules by their names.
//     $modules = system_rebuild_module_data();
//     uasort($modules, 'system_sort_modules_by_info_name');
//
//     $options = [NULL => t('Select a module')];
//     foreach($modules as $module_id => $module) {
//         $options[$module_id] = $module->info['name'];
//     }
//
//     $form['module'] = [
//         '#type' => 'select',
//         '#title' => $this->t('Select a module to validate'),
//         '#default_value' => $this->configuration['module'],
//         '#options' => $options,
//         '#description' => $this->t('Module selected status will be use to evaluate condition.'),
//     ];
//
//     return parent::buildConfigurationForm($form, $form_state);
// }

///**
// * {@inheritdoc}
// */
// public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
//     $this->configuration['module'] = $form_state->getValue('module');
//     parent::submitConfigurationForm($form, $form_state);
// }
//
///**
// * {@inheritdoc}
// */
// public function defaultConfiguration() {
//    return ['module' => ''] + parent::defaultConfiguration();
// }
//
///**
//  * Evaluates the condition and returns TRUE or FALSE accordingly.
//  *
//  * @return bool
//  *   TRUE if the condition has been met, FALSE otherwise.
//  */
//  public function evaluate() {
//      if (empty($this->configuration['module']) && !$this->isNegated()) {
//          return TRUE;
//      }
//
//      $module = $this->configuration['module'];
//      $modules = system_rebuild_module_data();
//
//      return $modules[$module]->status;
//  }
//
///**
// * Provides a human readable summary of the condition's configuration.
// */
// public function summary()
// {
//     $module = $this->getContextValue('module');
//     $modules = system_rebuild_module_data();
//
//     $status = ($modules[$module]->status)?t('enabled'):t('disabled');
//
//     return t('The module @module is @status.', ['@module' => $module, '@status' => $status]);
// }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['audiences'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the viewer has the following audience'),
      '#default_value' => $this->configuration['audiences'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->audience_manager->getOptionsList()),
      '#description' => $this->t('If you select no audience, the condition will
        evaluate to TRUE for all viewers.'),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'audiences' => array(),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['audiences'] = array_filter($form_state->getValue('audiences'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $audiences = array_intersect_key($this->audience_manager->getOptionsList(), $this->configuration['audiences']);
    if (count($audiences) > 1) {
      $audiences = implode(', ', $audiences);
    }
    else {
      $audiences = reset($audiences);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The viewer is not a member of @audiences', array('@audiences' => $audiences));
    }
    else {
      return $this->t('The viewer is a member of @audiences', array('@audiences' => $audiences));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['audiences']) && !$this->isNegated()) {
      return TRUE;
    }
    $audience = $this->getContextValue('audience');
    return (bool) array_intersect($this->configuration['audiences'], $audience);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = $contexts = [];
    foreach (parent::getCacheContexts() as $context) {
      $contexts[] = 'audience' ? 'audience' : $context;
    }

    return $contexts;
  }


}
