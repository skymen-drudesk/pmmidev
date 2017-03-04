<?php

namespace Drupal\pmmi_sso\Form;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Drupal\user\RoleInterface;
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
    $form['sso'] = array(
      '#type' => 'fieldset',
      '#title' => 'Personify SSO Services Data',
      '#description' => $this->t("Personify SSO service URI's and authentication data"),
    );
    $form['login_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URI'),
      '#maxlength' => 128,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('login_uri'),
    ];
    $form['service_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service URI'),
      '#maxlength' => 128,
      '#group' => 'sso',
      '#required' => TRUE,
      '#size' => 64,
      '#default_value' => $config->get('service_uri'),
    ];
    $form['vi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor Identifier'),
      '#maxlength' => 64,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vi'),
    ];
    $form['vu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vu'),
    ];
    $form['vp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor password (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vp'),
    ];
    $form['vib'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor initilization block (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#group' => 'sso',
      '#required' => TRUE,
      '#default_value' => $config->get('vib'),
    ];
    $form['ims'] = array(
      '#type' => 'fieldset',
      '#title' => 'Personify SSO IMS Data',
      '#group' => 'sso',
      '#description' => $this->t("Personify IM service URI and authentication data"),
    );
    $form['ims_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IM Service URI'),
      '#maxlength' => 128,
      '#group' => 'ims',
      '#required' => TRUE,
      '#size' => 64,
      '#default_value' => $config->get('ims_uri'),
    ];
    $form['ims_vu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IMS vendor username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#group' => 'ims',
      '#required' => TRUE,
      '#default_value' => $config->get('ims_vu'),
    ];
    $form['ims_vp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IMS vendor password (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#group' => 'ims',
      '#required' => TRUE,
      '#default_value' => $config->get('ims_vp'),
    ];
    $form['data_service'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Personify Data Service Information'),
      '#tree' => TRUE,
      '#description' => $this->t('Personify Endpoint and authentication data for the  PMMI Data Service'),
    );
    $form['data_service']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify endpoint'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('data_service.endpoint'),
    ];
    $form['data_service']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('data_service.username'),
    ];
    $form['data_service']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify password'),
      '#maxlength' => 32,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('data_service.password'),
    ];
    $form['user_accounts'] = array(
      '#type' => 'details',
      '#title' => $this->t('SSO User Account Management'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $auto_assigned_roles = $config->get('user_accounts.auto_assigned_roles');
    $form['user_accounts']['auto_assigned_roles_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically assign roles on user registration'),
      '#default_value' => count($auto_assigned_roles) > 0,
      '#states' => array(
        'invisible' => array(
          'input[name="user_accounts[auto_register]"]' => array('checked' => FALSE),
        ),
      ),
    );
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $form['user_accounts']['auto_assigned_roles'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Roles'),
      '#description' => t('The selected roles will be automatically assigned to each SSO user on login. Use this to automatically give SSO users additional privileges or to identify SSO users to other modules.'),
      '#default_value' => $auto_assigned_roles,
      '#options' => $roles,
    );
    $form['user_accounts']['login_link_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Login Link Enabled'),
      '#description' => $this->t('Display a link to login via SSO above the user login form.'),
      '#default_value' => $config->get('user_accounts.login_link_enabled'),
    );
    $form['user_accounts']['login_link_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login Link Label'),
      '#description' => $this->t('The text that makes up the login link to this SSO server.'),
      '#default_value' => $config->get('user_accounts.login_link_label'),
      '#states' => array(
        'visible' => array(
          ':input[name="user_accounts[login_link_enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['gateway'] = array(
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login) & Token Handling'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t('This implements the Gateway feature from the Personify SSO.' .
        'When enabled, Drupal will check if a visitor is already logged into your SSO server before ' .
        'serving a page request. If they have an active SSO session, they will be automatically ' .
        'logged into the Drupal site. This is done by quickly redirecting them to the SSO server to perform the ' .
        'active session check, and then redirecting them back to page they initially requested.<br/><br/>' .
        'If enabled, all pages on your site will trigger this feature by default. It is strongly recommended that ' .
        'you specify specific pages to trigger this feature below.<br/><br/>' .
        '<strong>WARNING:</strong> This feature will disable page caching  for anonymous users on pages it is active on.'),
    );
    $form['gateway']['check_frequency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Check Frequency'),
      '#default_value' => $config->get('gateway.check_frequency'),
      '#options' => array(
        PMMISSOHelper::CHECK_NEVER => 'Disable gateway feature',
        PMMISSOHelper::CHECK_ONCE => 'Once per browser session',
        PMMISSOHelper::CHECK_ALWAYS => 'Every page load (not recommended)',
      ),
    );
    $form['gateway']['token_frequency'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Check Token Frequency'),
      '#default_value' => $config->get('gateway.token_frequency'),
      '#options' => array(
        PMMISSOHelper::TOKEN_DISABLED => 'Disable feature',
        PMMISSOHelper::TOKEN_TTL => 'Every page load, if token TTL expired',
      ),
      '#description' => $this->t(
        'This implements the Token TTL feature for Drupal. When enabled, Drupal 
         will check if a visitor have valid token, in the time interval, 
         specified on this page: <a href="@link">Token settings page</a>.<br/>
         If enabled, all pages on your site will trigger this feature by 
         default. It is strongly recommended that you specify specific pages 
         to trigger this feature below.<br/>
         <strong>WARNING:</strong> This feature will disable page caching on 
         pages it is active on.', ['@link' => Url::fromRoute('pmmi_sso.token.settings')->toString()]
      ),
    );
    $form['gateway']['token_action'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default action for the failed Token validation result'),
      '#default_value' => $config->get('gateway.token_action'),
      '#options' => array(
        PMMISSOHelper::TOKEN_ACTION_LOGOUT => 'Logout from Drupal',
        PMMISSOHelper::TOKEN_ACTION_FORCE_LOGIN => 'Forced redirect to Personify Login Page',
      ),
      '#states' => array(
        'invisible' => array(
          ':input[name="gateway[token_frequency]"]' => array('value' => PMMISSOHelper::TOKEN_DISABLED),
        ),
      ),
      '#description' => $this->t(
        'This feature only implemented on selected pages below or for all pages, 
         if  no selected.<br/>
         If selected action Logout: If token is expired and not valid, after 
         verification, users will be logged out and stay on site.<br/>
         If selected action Forced Redirect: If token is expired and not valid, 
         after verification, users will be redirected to the Personify site.
         <br/>'
      ),
    );
    $this->gatewayPaths->setConfiguration($config->get('gateway.paths'));
    $form['gateway']['paths'] = $this->gatewayPaths->buildConfigurationForm(array(), $form_state);
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['advanced']['debug_log'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug information?'),
      '#description' => $this->t(
        'This is not meant for production sites! Enable this to log debug 
        information about the interactions with the PMMI SSO Server 
        to the Drupal log.'),
      '#default_value' => $config->get('advanced.debug_log'),
    );
    $form['advanced']['connection_timeout'] = array(
      '#type' => 'textfield',
      '#size' => 3,
      '#title' => $this->t('Connection timeout'),
      '#field_suffix' => $this->t('seconds'),
      '#description' => $this->t(
        'This module makes HTTP requests to your PMMI SSO server. 
        This value determines the maximum amount of time to wait
        on those requests before canceling them.'),
      '#default_value' => $config->get('advanced.connection_timeout'),
    );
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
      ->set('vib', $form_state->getValue('vib'))
      ->set('ims_uri', $form_state->getValue('ims_uri'))
      ->set('ims_vu', $form_state->getValue('ims_vu'))
      ->set('ims_vp', $form_state->getValue('ims_vp'))
      ->set('data_service.endpoint', $form_state->getValue([
        'data_service',
        'endpoint',
      ]))
      ->set('data_service.username', $form_state->getValue([
        'data_service',
        'username',
      ]))
      ->set('data_service.password', $form_state->getValue([
        'data_service',
        'password',
      ]))
      ->set('user_accounts.login_link_enabled', $form_state->getValue([
        'user_accounts',
        'login_link_enabled',
      ]))
      ->set('user_accounts.login_link_label', $form_state->getValue([
        'user_accounts',
        'login_link_label',
      ]));
    $auto_assigned_roles = [];
    if ($form_state->getValue([
      'user_accounts',
      'auto_assigned_roles_enable'
    ])
    ) {
      $auto_assigned_roles = array_keys($form_state->getValue([
        'user_accounts',
        'auto_assigned_roles'
      ]));
    }
    $config
      ->set('user_accounts.auto_assigned_roles', $auto_assigned_roles);
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('gateway.check_frequency', $form_state->getValue([
        'gateway',
        'check_frequency',
      ]))
      ->set('gateway.token_frequency', $form_state->getValue([
        'gateway',
        'token_frequency',
      ]))
      ->set('gateway.token_action', $form_state->getValue([
        'gateway',
        'token_action',
      ]))
      ->set('gateway.paths', $this->gatewayPaths->getConfiguration());
    $config
      ->set('advanced.debug_log', $form_state->getValue([
        'advanced',
        'debug_log',
      ]))
      ->set('advanced.connection_timeout', $form_state->getValue([
        'advanced',
        'connection_timeout',
      ]));
    $config->save();
  }

}
