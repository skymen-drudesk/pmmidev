<?php

namespace Drupal\pmmi_sso\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PMMISSOUpdateSettings.
 *
 * @package Drupal\pmmi_sso\Form
 */
class PMMISSOUpdateSettings extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountInterface $current_user,
    CronInterface $cron,
    QueueFactory $queue,
    StateInterface $state) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->queue = $queue;
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('queue'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_sso_update.settings',
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
    $config = $this->config('pmmi_sso_update.settings');
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cron jobs'),
      '#description' => $this->t('Enable cron jobs to update information'),
      '#default_value' => $config->get('enabled'),
    );


    $form['cron_users'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t("User's cron options"),
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $users_execution = \Drupal::state()->get('cron_pmmi_sso.users_execution');
    $users_execution = !empty($users_execution) ? $users_execution : REQUEST_TIME;

    $args = array(
      '%time' => date_iso8601(\Drupal::state()->get('cron_pmmi_sso.users_execution')),
      '%seconds' => $users_execution - REQUEST_TIME,
    );
    $form['cron_users']['status'] = array(
      '#type' => 'item',
      '#markup' => $this->t('<p>PMMI SSO (Users) Cron will next execute the first time cron runs after %time (%seconds seconds from now)</p>', $args),
    );
    $options = array(
      60 => $this->t('1 minute'),
      300 => $this->t('5 minutes'),
      3600 => $this->t('1 hour'),
      10800 => $this->t('3 hour'),
      21600 => $this->t('6 hour'),
      43200 => $this->t('12 hour'),
      86400 => $this->t('1 day'),
      172800 => $this->t('2 day'),
    );
    $form['cron_users']['main_interval_users'] = array(
      '#type' => 'select',
      '#title' => $this->t("Main interval"),
      '#description' => $this->t('Time after which pmmi_sso_users cron will respond to a processing request.'),
      '#default_value' => $config->get('main_interval_users'),
      '#options' => $options,
      '#states' => array(
        'required' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_users']['interval_block'] = array(
      '#type' => 'number',
      '#title' => $this->t("Interval: Block User's"),
      '#min' => 100,
      '#step' => 100,
      '#description' => $this->t('Update time: checking, if user still active in the Personify Services.'),
      '#default_value' => $config->get('interval_block'),
      '#field_suffix' => $this->t('sec'),
      '#states' => array(
        'required' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_users']['interval_info'] = array(
      '#type' => 'number',
      '#title' => $this->t("Interval: User's Information"),
      '#min' => 100,
      '#step' => 100,
      '#description' => $this->t('Update time: updating user information (FirstName, LastName, LabelName).'),
      '#default_value' => $config->get('interval_info'),
      '#field_suffix' => $this->t('sec'),
      '#states' => array(
        'required' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_users']['interval_roles'] = array(
      '#type' => 'number',
      '#title' => $this->t("Interval: User's Roles"),
      '#min' => 100,
      '#step' => 100,
      '#description' => $this->t('Update time: updating allowed user roles.'),
      '#default_value' => $config->get('interval_roles'),
      '#field_suffix' => $this->t('sec'),
      '#states' => array(
        'required' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_users']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add jobs to queue users'),
      '#submit' => [[$this, 'addUsers']],
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
        'disabled' => array(
          ':input[name="enabled"]' => array('checked' => FALSE),
        ),
      ),
    );

    // Personify Companies cron settings.
    $pc_execution = \Drupal::state()->get('cron_pmmi_sso.pc_execution');
    $pc_execution = !empty($pc_execution) ? $pc_execution : REQUEST_TIME;

    $args = array(
      '%time' => date_iso8601(\Drupal::state()->get('cron_pmmi_sso.pc_execution')),
      '%seconds' => $pc_execution - REQUEST_TIME,
    );
    $form['cron_pc'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t("Companies cron options"),
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_pc']['status'] = array(
      '#type' => 'item',
      '#markup' => $this->t('<p>PMMI SSO (Companies) Cron will next execute the first time cron runs after %time (%seconds seconds from now)</p>', $args),
    );
    $form['cron_pc']['main_interval_companies'] = array(
      '#type' => 'select',
      '#title' => $this->t("Main interval"),
      '#description' => $this->t('Time after which pmmi_sso_personify_company cron will respond to a processing request.'),
      '#default_value' => $config->get('main_interval_companies'),
      '#options' => $options,
      '#states' => array(
        'required' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_pc']['interval_company'] = array(
      '#type' => 'number',
      '#title' => $this->t("Interval: Personify Company Entity"),
      '#min' => 100,
      '#step' => 100,
      '#description' => $this->t('Update time: Personify Company Entity.'),
      '#default_value' => $config->get('interval_company'),
      '#field_suffix' => $this->t('sec'),
      '#states' => array(
        'required' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['cron_pc']['cron_queue_company']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add jobs to queue Personify Company'),
      '#submit' => [[$this, 'addCompanies']],
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
        'disabled' => array(
          ':input[name="enabled"]' => array('checked' => FALSE),
        ),
      ),
    );

    $queue_users = $this->queue->get('pmmi_sso_users');
    $queue_pc = $this->queue->get('pmmi_sso_personify_companies');

    $args = [
      '%queue_users' => $queue_users->numberOfItems(),
      '%queue_pc' => $queue_pc->numberOfItems(),
    ];
    $form['current_cron_queue_status'] = array(
      '#type' => 'item',
      '#markup' => $this->t('There are currently %queue_users items in queue Users and %queue_pc items in queue Personify Company', $args),
    );


    if ($this->currentUser->hasPermission('administer site configuration')) {
      $form['cron_run'] = array(
        '#type' => 'details',
        '#title' => $this->t('Run cron manually'),
        '#open' => TRUE,
        '#states' => array(
          'visible' => array(
            ':input[name="enabled"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['cron_run']['cron_reset'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t("Run cron regardless of whether interval has expired."),
        '#default_value' => FALSE,
      );
      $form['cron_run']['cron_trigger']['actions'] = array('#type' => 'actions');
      $form['cron_run']['cron_trigger']['actions']['sumbit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#submit' => [[$this, 'cronRun']],
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {
    $config = $this->configFactory->getEditable('pmmi_sso_update.settings');

    $cron_reset = $form_state->getValue('cron_reset');
    if (!empty($cron_reset)) {
      \Drupal::state()->set('cron_pmmi_sso.users_execution', 0);
      \Drupal::state()->set('cron_pmmi_sso.pc_execution', 0);
    }

    if ($this->cron->run()) {
      drupal_set_message($this->t('Cron ran successfully.'));
    }
    else {
      drupal_set_message($this->t('Cron run failed.'), 'error');
    }
  }

  /**
   * Add the items to the queue when signaled by the form.
   */
  public function addUsers(array &$form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $queue_name = $form['cron_queue_setup']['queue'][$values['queue']]['#title'];
    $num_items = $form_state->getValue('num_items');
    // Queues are defined by a QueueWorker Plugin which are selected by their
    // id attritbute.
    // @see \Drupal\cron_example\Plugin\QueueWorker\ReportWorkerOne
    $queue = $this->queue->get($values['queue']);

    for ($i = 1; $i <= $num_items; $i++) {
      // Create a new item, a new data object, which is passed to the
      // QueueWorker's processItem() method.
      $item = new \stdClass();
      $item->created = REQUEST_TIME;
      $item->sequence = $i;
      $queue->createItem($item);
    }

    $args = [
      '%num' => $num_items,
      '%queue' => $queue_name,
    ];
    drupal_set_message($this->t('Added %num items to %queue', $args));
  }

  /**
   * Add the items to the queue when signaled by the form.
   */
  public function addCompanies(array &$form, FormStateInterface &$form_state) {
    $queue_name = $this->t('PMMI Personify Company Queue');
    $num_items = $form_state->getValue('num_items');
    // Queues are defined by a QueueWorker Plugin which are selected by their
    // id attritbute.
    // @see \Drupal\cron_example\Plugin\QueueWorker\ReportWorkerOne
    $queue = $this->queue->get('');



    for ($i = 1; $i <= $num_items; $i++) {
      // Create a new item, a new data object, which is passed to the
      // QueueWorker's processItem() method.
      $item = new \stdClass();
      $item->created = REQUEST_TIME;
      $item->sequence = $i;
      $queue->createItem($item);
    }

    $args = [
      '%num' => $num_items,
      '%queue' => $queue_name,
    ];
    drupal_set_message($this->t('Added %num items to PMMI Personify Companies Queue', $args));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('pmmi_sso_update.settings');

    $config
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('main_interval_users', $form_state->getValue('main_interval_users'))
      ->set('interval_block', $form_state->getValue('interval_block'))
      ->set('interval_info', $form_state->getValue('interval_info'))
      ->set('interval_roles', $form_state->getValue('interval_roles'))
      ->set('main_interval_companies', $form_state->getValue('main_interval_companies'))
      ->set('interval_company', $form_state->getValue('interval_company'));
    $config->save();
  }

}
