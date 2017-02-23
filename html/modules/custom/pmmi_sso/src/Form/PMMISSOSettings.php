<?php

namespace Drupal\pmmi_sso\Form;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PMMISSOSettings.
 *
 * @package Drupal\pmmi_sso\Form
 */
class PMMISSOSettings extends ConfigFormBase {

  /**
   * RequestPath condition that contains the paths to use for gateway.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $gatewayPaths;

  /**
   * Constructs a \Drupal\pmmi_sso\Form\PMMISSOSettings object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    parent::__construct($config_factory);
    $this->gatewayPaths = $plugin_factory->createInstance('request_path');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_sso.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sso_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_sso.settings');
    $form['login_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URI'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('login_uri'),
    ];
    $form['service_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service URI'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('service_uri'),
    ];
    $form['vi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor Identifier'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vi'),
    ];
    $form['vu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vu'),
    ];
    $form['vp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor password (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vp'),
    ];
    $form['vib'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor initilization block (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vib'),
    ];
    $form['gateway'] = array(
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login)'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t('This implements the Gateway feature from the Personify SSO.' .
        'When enabled, Drupal will check if a visitor is already logged into your SSO server before ' .
        'serving a page request. If they have an active SSO session, they will be automatically ' .
        'logged into the Drupal site. This is done by quickly redirecting them to the SSO server to perform the ' .
        'active session check, and then redirecting them back to page they initially requested.<br/><br/>' .
        'If enabled, all pages on your site will trigger this feature by default. It is strongly recommended that ' .
        'you specify specific pages to trigger this feature below.<br/><br/>' .
        '<strong>WARNING:</strong> This feature will disable page caching on pages it is active on.'),
    );
    $form['gateway']['check_frequency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Check Frequency'),
      '#default_value' => $config->get('gateway.check_frequency'),
      '#options' => array(
        PMMISSOHelper::CHECK_NEVER => 'Disable gateway feature',
        PMMISSOHelper::CHECK_ONCE => 'Once per browser session',
        PMMISSOHelper::CHECK_ALWAYS => 'Every page load (not recommended)',
        PMMISSOHelper::CHECK_TOKEN_TTL => 'Every page load, if token TTL not expired',
      ),
    );
    $this->gatewayPaths->setConfiguration($config->get('gateway.paths'));
    $form['gateway']['paths'] = $this->gatewayPaths->buildConfigurationForm(array(), $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->validateConfigurationForm($form, $condition_values);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('pmmi_sso.settings');

    $config
      ->set('login_uri', $form_state->getValue('login_uri'))
      ->set('service_uri', $form_state->getValue('service_uri'))
      ->set('vi', $form_state->getValue('vi'))
      ->set('vu', $form_state->getValue('vu'))
      ->set('vp', $form_state->getValue('vp'))
      ->set('vib', $form_state->getValue('vib'));
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('gateway.check_frequency', $form_state->getValue([
        'gateway',
        'check_frequency'
      ]))
      ->set('gateway.paths', $this->gatewayPaths->getConfiguration());
    $config->save();
  }

}
