<?php
//
//namespace Drupal\current_audience\Plugin\Condition;
//
//use Drupal\audience_select\Service\AudienceManager;
//use Drupal\Core\Condition\ConditionPluginBase;
//use Drupal\Core\Form\FormStateInterface;
//
///**
// * Provides a 'Audience' condition.
// *
// * @Condition(
// *   id = "audience",
// *   label = @Translation("Audience"),
// *   context = {
// *     "audience" = @ContextDefinition("configuration:audience",
// *       label = @Translation("Audience"))
// *   }
// * )
// */
//class CurrentAudienceCondition_old extends ConditionPluginBase {
//
//  /**
//   * The audience manager service.
//   *
//   * @var \Drupal\audience_select\Service\AudienceManager
//   */
//  protected $audience_manager;
//
//  /**
//   * The configured audiences.
//   *
//   * @var null
//   */
//  protected $audiences;
//
//  /**
//   * {@inheritdoc}
//   */
//  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//    $this->audience_manager = new AudienceManager();
//    $this->audiences = $this->audience_manager->getGatewayAudiences();
//
//    $form['audiences'] = array(
//      '#type' => 'select',
//      '#title' => $this->t('When the viewer has the following audience'),
//      '#default_value' => $this->configuration['audiences'],
//      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->audiences),
//      '#description' => $this->t('If you select no audience, the condition will
//        evaluate to TRUE for all viewers.'),
//    );
//    return parent::buildConfigurationForm($form, $form_state);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function defaultConfiguration() {
//    $this->audience_manager = new AudienceManager();
//    $this->audiences = $this->audience_manager->getGatewayAudiences();
//    return array(
//      'audiences' => $this->audiences,
//    );
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
//    $this->configuration['audiences'] = array_filter($form_state->getValue('audiences'));
//    parent::submitConfigurationForm($form, $form_state);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function summary() {
//    $this->audience_manager = new AudienceManager();
//    // Use the audience gateway labels. They will be sanitized below.
//    $this->audiences = $this->audience_manager->getGatewayAudiences();
//    $audiences = array_intersect_key($this->audiences, $this->configuration['audiences']);
//    if (count($audiences) > 1) {
//      $audiences = implode(', ', $audiences);
//    }
//    else {
//      $audiences = reset($audiences);
//    }
//    if (!empty($this->configuration['negate'])) {
//      return $this->t('The viewer is not a member of @audiences', array('@audiences' => $audiences));
//    }
//    else {
//      return $this->t('The viewer is a member of @audiences', array('@audiences' => $audiences));
//    }
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function evaluate() {
//    if (empty($this->configuration['audiences']) && !$this->isNegated()) {
//      return TRUE;
//    }
//    $audience = $this->getContextValue('audience');
//    return (bool) array_intersect($this->configuration['audiences'], $audience);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function getCacheContexts() {
//    $contexts = $contexts = [];
//    foreach (parent::getCacheContexts() as $context) {
//      $contexts[] = 'audience' ? 'audience' : $context;
//    }
//
//    return $contexts;
//  }
//
//}
