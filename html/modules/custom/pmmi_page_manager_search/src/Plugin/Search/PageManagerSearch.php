<?php

namespace Drupal\pmmi_page_manager_search\Plugin\Search;

use Drupal\Component\Utility\Unicode;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\pmmi_page_manager_search\Entity;

/**
 * Handles searching for Page Manager entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "page_manager_search",
 *   title = @Translation("Pages")
 * )
 */
class PageManagerSearch extends SearchPluginBase implements SearchIndexingInterface {

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
   * Updates the search index for this plugin.
   *
   * This method is called every cron run if the plugin has been set as
   * an active search module on the Search settings page
   * (admin/config/search/pages). It allows your module to add items to the
   * built-in search index using search_index(), or to add them to your module's
   * own indexing mechanism.
   *
   * When implementing this method, your module should index content items that
   * were modified or added since the last run. There is a time limit for cron,
   * so it is advisable to limit how many items you index per run using
   * config('search.settings')->get('index.cron_limit') or with your own
   * setting. And since the cron run could time out and abort in the middle of
   * your run, you should update any needed internal bookkeeping on when items
   * have last been indexed as you go rather than waiting to the end of
   * indexing.
   */
  public function updateIndex() {
    $page_variants = \Drupal::entityTypeManager()
      ->getStorage('page_variant')
      ->loadMultiple();

    foreach ($page_variants as $pid => $page_variant) {
      $page = Entity\PageManagerSearch::load($pid);
      $sid = pmmi_page_manager_search_encoder($pid);

      // @todo Check if not disabled.
      if (!empty($page->content)) {
        search_index($this->getType(), $sid, 'EN', $page->content);
      }
    }
  }

  /**
   * Clears the search index for this plugin.
   *
   * When a request is made to clear all items from the search index related to
   * this plugin, this method will be called. If this plugin uses the default
   * search index, this method can call search_index_clear($type) to remove
   * indexed items from the search database.
   *
   * @see search_index_clear()
   */
  public function indexClear() {
    search_index_clear($this->getPluginId());
  }

  /**
   * Marks the search index for reindexing for this plugin.
   *
   * When a request is made to mark all items from the search index related to
   * this plugin for reindexing, this method will be called. If this plugin uses
   * the default search index, this method can call
   * search_mark_for_reindex($type) to mark the items in the search database for
   * reindexing.
   *
   * @see search_mark_for_reindex()
   */
  public function markForReindex() {
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * Reports the status of indexing.
   *
   * The core search module only invokes this method on active module plugins.
   * Implementing modules do not need to check whether they are active when
   * calculating their return values.
   *
   * @return array
   *   An associative array with the key-value pairs:
   *   - remaining: The number of items left to index.
   *   - total: The total number of items to index.
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
   * Executes the search.
   *
   * @return array
   *   A structured list of search results.
   */
  public function execute() {
    $results = [];

    $keys = $this->keywords;

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
      $page = Entity\PageManagerSearch::load($item->sid, TRUE);
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