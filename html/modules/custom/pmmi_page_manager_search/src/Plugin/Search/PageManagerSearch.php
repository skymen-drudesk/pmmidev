<?php

namespace Drupal\pmmi_page_manager_search\Plugin\Search;

use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBase;

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
    // TODO: Implement updateIndex() method.

    // Interpret the cron limit setting as the maximum number of nodes to index
    // per cron run.
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $query = db_select('node', 'n', array('target' => 'replica'));
    $query->addField('n', 'nid');
    $query->leftJoin('search_dataset', 'sd', 'sd.sid = n.nid AND sd.type = :type', array(':type' => $this->getPluginId()));
    $query->addExpression('CASE MAX(sd.reindex) WHEN NULL THEN 0 ELSE 1 END', 'ex');
    $query->addExpression('MAX(sd.reindex)', 'ex2');
    $query->condition(
      $query->orConditionGroup()
        ->where('sd.sid IS NULL')
        ->condition('sd.reindex', 0, '<>')
    );
    $query->orderBy('ex', 'DESC')
      ->orderBy('ex2')
      ->orderBy('n.nid')
      ->groupBy('n.nid')
      ->range(0, $limit);

    $nids = $query->execute()->fetchCol();
    if (!$nids) {
      return;
    }

    $node_storage = $this->entityManager->getStorage('node');
    foreach ($node_storage->loadMultiple($nids) as $node) {
      $this->indexNode($node);
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
    // TODO: Implement indexClear() method.
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
    // TODO: Implement markForReindex() method.
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
    // TODO: Implement indexStatus() method.
  }

  /**
   * Executes the search.
   *
   * @return array
   *   A structured list of search results.
   */
  public function execute() {
    // TODO: Implement execute() method.
  }
}