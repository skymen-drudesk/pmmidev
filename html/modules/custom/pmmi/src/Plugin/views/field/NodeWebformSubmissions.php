<?php

/**
 * @file
 * Definition of Drupal\pmmi\views\field\NodeWebformSubmissions.
 */

namespace Drupal\pmmi\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_webform_submissions")
 */
class NodeWebformSubmissions extends FieldPluginBase {

  protected $webformStorage;

  protected $submissionStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->webformStorage = $entity_type_manager->getStorage('webform');
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * Define the available options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $links = [];
    $node = $values->_entity;
    foreach ($node->getFieldDefinitions() as $field) {
      if ($field instanceof FieldConfig) {
        if ($field->getType() == 'webform' && isset($node->{$field->getName()})) {
          $field_values = $node->{$field->getName()}->getValue();
          break;
        }
      }
    }
    if (isset($field_values) && !empty($field_values)) {
      foreach ($field_values as $value) {
        $entity = $this->webformStorage->load($value['target_id']);
        $submissions_count = $this->submissionStorage->getTotal($entity);
        if ($entity->access('submission_view_any')) {
          $links[] = [
            '#title' => $this->t('@label: @count submissions', ['@label' => $entity->label(), '@count' => $submissions_count]),
            '#type' => 'link',
            '#url' => Url::fromRoute('entity.webform.results_submissions', ['webform' => $entity->id()]),
          ];
        }
      }
    }
    return [
      '#theme' => 'item_list',
      '#items' => $links,
    ];
  }

}
