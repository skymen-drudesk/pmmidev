<?php

/**
 * @file
 * Contains \Drupal\pmmi_forms\Plugin\Block\EntityForm.
 */

namespace Drupal\pmmi_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a block to show a form of specific entity.
 *
 * @Block(
 *   id = "entity_form",
 *   category = @Translation("Forms"),
 *   deriver = "Drupal\pmmi_forms\Plugin\Deriver\EntityFormDeriver",
 * )
 */
class EntityForm extends BlockBase implements ContainerFactoryPluginInterface {

  protected $entityManager;

  protected $entityFormBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = $this->getContextValue('entity');

    if ($entity->getEntityTypeId() == 'node_type') {
      $entity = $this->entityManager->getStorage('node')->create(array(
        'type' => $entity->id(),
      ));
    }

    $form = $this->entityFormBuilder->getForm($entity);

    return $form;
  }

}
