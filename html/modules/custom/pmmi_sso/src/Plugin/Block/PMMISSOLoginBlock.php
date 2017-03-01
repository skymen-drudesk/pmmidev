<?php

namespace Drupal\pmmi_sso\Plugin\Block;

use DateTime;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Rijndael;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'PMMISSOLoginBlock' block.
 *
 * @Block(
 *  id = "pmmi_sso_login_block",
 *  admin_label = @Translation("PMMI SSO Login Block"),
 *  category = @Translation("PMMI")
 * )
 */
class PMMISSOLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;
  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;
  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $personifySSOConfig;

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
    RequestStack $request_stack,
    ConfigFactory $config_factory,
    CurrentPathStack $path_current
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->pathCurrent = $path_current;
    $this->personifySSOConfig = $config_factory->get('pmmi_sso.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'label' => $this->t(''),
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['label'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['label'] = $form_state->getValue('label');
  }

  /**
   * {@inheritdoc}
   */
  public function hex2str($func_string) {
    $func_retVal = '';
    $func_length = strlen($func_string);
    for($func_index = 0; $func_index < $func_length; ++$func_index) $func_retVal .= chr(hexdec($func_string{$func_index} . $func_string{++$func_index}));

    return $func_retVal;
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
//    /** @var \Drupal\pmmi_sso\Service\PMMISSOService $sso_service */
//    $sso_service = \Drupal::service('pmmi_sso.service');

    $cipher = new Rijndael();
    $now = DateTime::createFromFormat('U.u', microtime(TRUE));
    $timestamp = $now->format('YmdHisv');
    $request = $this->requestStack->getCurrentRequest();
    $current_url = Url::fromUserInput('/ssoservice', ['absolute' => TRUE]);
    $query = $request->query->all();
    $query['returnto'] = $request->getPathInfo();

    $current_url->setOption('query', $query);

    $return_uri = $current_url->toString();
    $string = $timestamp . '|' . $return_uri;

    $vi = $this->personifySSOConfig->get('vi');
    $vp = hex2bin($this->personifySSOConfig->get('vp'));
    $vib = hex2bin($this->personifySSOConfig->get('vib'));

    $cipher->setKey($vp);
    $cipher->setIV($vib);
    $cipher->setBlockLength(128);
    $cipher->setKeyLength(128);
    $token = bin2hex($cipher->encrypt($string));

//    $str = $sso_service->encrypt($string);
//    $dat = $cipher->decrypt(hex2bin($token));

    $url = Url::fromUri($this->personifySSOConfig->get('login_uri'));
    $url->setAbsolute(TRUE);
    $url->setOption('query', ['vi' => $vi, 'vt' => $token]);
    $url = Url::fromRoute('pmmi_sso.login');
    $build = [
      '#type' => 'link',
      '#title' => $this->t('Login'),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];

    return $build;
  }

}
