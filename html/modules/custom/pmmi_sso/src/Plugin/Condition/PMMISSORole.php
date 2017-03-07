<?php

namespace Drupal\audience_select\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'pmmi_sso_role' condition to enable a condition.
 *
 * @Condition(
 *   id = "pmmi_sso_roles",
 *   label = @Translation("PMMI SSO role"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"), required = TRUE)
 *   }
 * )
 */
class PMMISSORole extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The contact settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $allowedSsoRoles;

  /**
   * An authmap service object.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authMap;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface;
   */
  protected $userData;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('externalauth.authmap'),
      $container->get('user.data')
    );
  }

  /**
   * Creates a new AudienceCondition instance.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   An authmap service object.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AuthmapInterface $authmap,
    UserDataInterface $user_data
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->allowedSsoRoles = $config_factory->get('pmmi_sso.settings')->get('user_accounts.sso_roles');
    $this->authMap = $authmap;
    $this->ssoRoles = $user_data->get('pmmi_sso', $this->getContextValue('user')->id(), 'sso_roles');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = ['PMMI Member', 'STAFF'];
    $form['pmmi_sso_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the viewer has the following SSO Roles'),
      '#default_value' => $this->configuration['audiences'],
      '#options' => $options,
      '#description' => $this->t('If you select no SSO roles, the condition will
        evaluate to TRUE for all viewers.'),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
        'pmmi_sso_roles' => array(),
      ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['pmmi_sso_roles'] = array_filter($form_state->getValue('pmmi_sso_roles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
//    $sso_roles = array_intersect_key($this->AudienceManager->getOptionsList(), $this->configuration['pmmi_sso_role']);
    $sso_roles = ['PMMI Member', 'STAFF'];

    if (count($sso_roles) > 1) {
      $sso_roles = implode(', ', $sso_roles);
    }
    else {
      $sso_roles = reset($sso_roles);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The viewer do not have following roles: @pmmi_sso_roles', array('@pmmi_sso_roles' => $sso_roles));
    }
    else {
      return $this->t('The viewer has the following roles: @pmmi_sso_roles', array('@pmmi_sso_roles' => $sso_roles));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['pmmi_sso_role']) && !$this->isNegated()) {
      return TRUE;
    }
    $user = $this->getContextValue('user');
    $roles = $this->authMap->getAuthData($user->id(), 'pmmi_sso')['sso_roles'];
    return (bool) array_intersect($this->configuration['pmmi_sso_roles'], $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Optimize cache context, if a user cache context is provided, only use
    // user.roles, since that's the only part this condition cares about.
    $contexts = [];
    foreach (parent::getCacheContexts() as $context) {
      $contexts[] = $context == 'user' ? 'user.pmmi_sso_roles' : $context;
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:pmmi_sso.settings';
    return $cache_tags;
  }

//  /**
//   * {@inheritdoc}
//   */
//  public function getCacheContexts() {
//    // Optimize cache context, if a user cache context is provided, only use
//    // user.roles, since that's the only part this condition cares about.
//    $contexts = [];
//    foreach (parent::getCacheContexts() as $context) {
//      $contexts[] = $context == 'user' ? 'user.roles' : $context;
//    }
//    return $contexts;
//  }
}
