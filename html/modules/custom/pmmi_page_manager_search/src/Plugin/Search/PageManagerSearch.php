<?php

namespace Drupal\pmmi_page_manager_search\Plugin\Search;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for Page Manager entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "page_manager_search",
 *   title = @Translation("Search Pages")
 * )
 */
class PageManagerSearch extends ConfigurableSearchPluginBase implements SearchIndexingInterface {

  /**
   * An entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager, Connection $database) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    $page_variants = $this->entityTypeManager
      ->getStorage('page_variant')
      ->loadMultiple();

    foreach ($page_variants as $pid => $page_variant) {
      $sid = pmmi_page_manager_search_machine_name_to_dec($pid);
      $page = $this->entityTypeManager
        ->getStorage('page_manager_search')
        ->load($sid);

      if (!empty($page->content)) {
        search_index($this->getType(), $sid, 'EN', $page->content);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    search_index_clear($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $page_variant = $this->entityTypeManager
      ->getStorage('page_variant')
      ->loadMultiple();

    $total = count($page_variant);
    $remaining = $total - $this->database
        ->query("SELECT COUNT(*) FROM {search_dataset} sd WHERE sd.type = :type AND sd.reindex = 0", [':type' => $this->getPluginId()])
        ->fetchField();

    return ['remaining' => $remaining, 'total' => $total];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $results = [];

    $keys = $this->keywords;

    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', ['target' => 'replica'])
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->searchExpression($keys, $this->getType());

    $find = $query
      ->limit(10)
      ->execute();

     foreach ($find as $item) {
      $page = $this->entityTypeManager
        ->getStorage('page_manager_search')
        ->load($item->sid);

      if (!empty($page)) {
        $results[] = [
          'link' => $page->path,
          'title' => $page->title,
          'score' => $item->calculated_score,
          'snippet' => search_excerpt($keys, $page->content, $item->langcode),
        ];
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }
}