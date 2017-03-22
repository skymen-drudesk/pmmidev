<?php

namespace Drupal\pmmi_psdata\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Drupal\Core\Cache\DatabaseBackend;

/**
 * Class PMMIDataCollector.
 *
 * @package Drupal\pmmi_psdata
 */
class PMMIDataCollector {

  /**
   * Drupal\Core\Cache\DatabaseBackend definition.
   *
   * @var \Drupal\Core\Cache\DatabaseBackend
   */
  protected $cache;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  protected $provider = PMMISSOHelper::PROVIDER;

  /**
   * PMMIDataCollector constructor.
   *
   * @param \Drupal\Core\Cache\DatabaseBackend $cache_default
   *   The cache to use for the Personify service data.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue plugin manager.
   */
  public function __construct(
    DatabaseBackend $cache_default,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    PMMISSOHelper $sso_helper,
    QueueFactory $queue,
    QueueWorkerManagerInterface $queue_manager
  ) {
    $this->cache = $cache_default;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->ssoHelper = $sso_helper;
    $this->queue = $queue;
    $this->queueManager = $queue_manager;
  }

  /**
   * Get Members Data.
   *
   * @param string $data_type
   *   The type of requested data.
   * @param string $id
   *   The ID of requested data.
   *
   * @return array
   *   The array of data.
   */
  public function getData($data_type, $id) {
    $data = NULL;
    // Cache ID.
    $cid = $this->provider . ':' . $data_type . '_' . $id;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    // Queue ID.
    $qid = 'pmmi_psdata_' . $data_type . '_real';
    $this->cacheTagsInvalidator->invalidateTags([$cid]);
    $queue = $this->queue->get($qid);
    $queue->createItem(['id' => $id, 'type' => $data_type]);
    $queue_worker = $this->queueManager->createInstance($qid);
    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      } catch (\Exception $e) {
        watchdog_exception('npq', $e);
      }
    }

    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    return $data;
  }

}
