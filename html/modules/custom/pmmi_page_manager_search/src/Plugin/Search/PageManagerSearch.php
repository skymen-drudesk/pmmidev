<?php

namespace Drupal\pmmi_page_manager_search\Plugin\Search;

use Drupal\Component\Utility\Unicode;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\pmmi_page_manager_search\Entity;

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
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    $page_variants = \Drupal::entityTypeManager()
      ->getStorage('page_variant')
      ->loadMultiple();

    foreach ($page_variants as $pid => $page_variant) {
      $sid = pmmi_page_manager_search_machine_name_to_dec($pid);
      $page = \Drupal::entityTypeManager()
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
    $page_variant = \Drupal::entityTypeManager()
      ->getStorage('page_variant')
      ->loadMultiple();

    $total = count($page_variant);
    $remaining = $total - \Drupal::database()
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
//
//    $page = \Drupal::entityTypeManager()
//      ->getStorage('page_manager_search')
//      ->loadMultiple();

    $b=0;

    // Build matching conditions.
    $query = \Drupal::database()
      ->select('search_index', 'i', ['target' => 'replica'])
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->searchExpression($keys, $this->getType());

    $find = $query
      ->limit(10)
      ->execute();

    foreach ($find as $item) {
      $page = \Drupal::entityTypeManager()
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
}