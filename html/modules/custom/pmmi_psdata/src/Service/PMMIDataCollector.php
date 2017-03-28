<?php

namespace Drupal\pmmi_psdata\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\pmmi_sso\Service\PMMISSOHelper;

/**
 * Class PMMIDataCollector.
 *
 * @package Drupal\pmmi_psdata
 */
class PMMIDataCollector {

  /**
   * The cache backend to use for the complete theme registry data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
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

  /**
   * Provider name.
   *
   * @var string
   */
  protected $provider = PMMISSOHelper::PROVIDER;

  /**
   * PMMIDataCollector constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_default
   *   The cache backend interface to use for the complete theme registry data.
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
    CacheBackendInterface $cache_default,
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
   * Get the main data array.
   *
   * @param object $options
   *   The type of requested data.
   *
   * @return array
   *   The array of data.
   */
  public function getData($options) {
    $data = NULL;
    $cid = $this->buildCid($options, 'main');
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    else {
      if ($options->type == 'company') {
        $this->buildCompanyData($options, $cid);
      }
      else {
        $qid = 'pmmi_psdata_committee_real';
        $this->processQueue($qid, $options);
      }
    }
    $this->invalidateTags([$cid]);
    return $this->cache->get($cid)->data;
  }

  /**
   * Wrapper for the function invalidateTags.
   *
   * @param array $tags
   *   The array og tags to invalidate.
   */
  public function invalidateTags(array $tags) {
    $this->cacheTagsInvalidator->invalidateTags($tags);
  }

  /**
   * Build company data array.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $main_cid
   *   Cache ID for the data.
   */
  private function buildCompanyData($options, $main_cid) {
    $data = [];
    // Get company data.
    $cid = $this->buildCid($options, 'company');
    if ($this->cache->get($cid)) {
      $company_data = $this->cache->get($cid)->data;
    }
    else {
      $company_data = $this->getCompanyData($options, $cid);
    }
    // Get staff data.
    $cid = $this->buildCid($options, 'staff');
    if ($this->cache->get($cid)) {
      $staff_data = $this->cache->get($cid)->data;
    }
    else {
      $staff_data = $this->getCompanyStaffData($options, $cid);
    }
    if (!empty($company_data) && !empty($staff_data)) {
      $data = $this->sortCompanyData($options, $company_data, $staff_data);
    }
    // Delete all empty values from array.
    $data = array_map('array_filter', $data);
    $data = array_filter($data);
    if ($data) {
      $tags = [
        $this->buildCid($options, 'company'),
        $this->buildCid($options, 'staff'),
      ];
      $this->cache->set($main_cid, $data, Cache::PERMANENT, $tags);
    }
  }

  /**
   * Sort company data array.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param array $company_data
   *   An array with main information about the company.
   * @param array $staff_data
   *   An array of information about the company's employees.
   *
   * @return array
   *   The main array with data about the company (sorted and filtered).
   */
  protected function sortCompanyData($options, array $company_data, array $staff_data) {
    $result = [];
    $country_code = $options->data['company']['country_code'];
    $result['company'] = $company_data[$options->id][$options->data['company']['country_code']];
    $staff_data = array_filter($staff_data, function ($row) use ($country_code) {
      return (
        array_key_exists('country', $row) && $row['country'] == $country_code
      );
    });
    if ($sort_empl = $options->data['company']['sort_empl']) {
      $result['company']['staff'] = $this->filterAndSortCommunications(
        $this->filterCompanyStaff($staff_data, $sort_empl),
        $options->data['company']['comm_empl']
      );
      $result['staff'] = $this->filterAndSortCommunications(
        array_diff_key($staff_data, $result['company']['staff']),
        $options->data['staff']['comm_empl']
      );
    }
    else {
      $result['staff'] = $this->filterAndSortCommunications(
        $staff_data,
        $options->data['staff']['comm_empl']
      );
    }
    if ($options->data['staff']['enabled']) {
      $result['staff'] = $this->sortByArrayKey($result['staff'], 'last_name');
    }
    return $result;
  }

  /**
   * Filter the company's employees to represent in the company section.
   *
   * @param array $array_to_filter
   *   An array of information about the company's employees.
   * @param array $filter_by
   *   An array of information about the necessary employees of the company for
   *   the company section.
   *
   * @return array
   *   Filtered array.
   */
  public function filterCompanyStaff(array $array_to_filter, array $filter_by) {
    $result = [];
    foreach ($filter_by as $employee) {
      $last_first_name = explode(' ', trim($employee));
      $result = array_merge($result, array_filter($array_to_filter, function ($row) use ($last_first_name) {
        return (
          strtolower(trim($row['first_name'])) == strtolower(trim($last_first_name[0])) &&
          strtolower(trim($row['last_name'])) == strtolower(trim($last_first_name[1]))
        );
      }));
    }
    return $result;
  }

  /**
   * Filter and sort information about the communication.
   *
   * @param array $array_to_sort
   *   An array of information about communication data.
   * @param array $array_by_sort
   *   An array of communication information that is allowed to be mapped.
   *
   * @return array
   *   Filtered and sorted array.
   */
  public function filterAndSortCommunications(array $array_to_sort, array $array_by_sort) {
    foreach ($array_to_sort as &$item) {
      if (!empty($item['comm'])) {
        // Get filter and sort keys.
        $sort_keys = array_flip(array_map('strtolower', $array_by_sort));
        // Get available keys in sorted array.
        $sort_keys = array_intersect_key($sort_keys, $item['comm']);
        $item['comm'] = array_intersect_key($item['comm'], $sort_keys);
        // Reorder values.
        $item['comm'] = array_replace($sort_keys, $item['comm']);
      }
    }
    return $array_to_sort;
  }

  /**
   * Sort information by array key.
   *
   * @param array $array_to_sort
   *   An array of information for sorting.
   * @param string $key
   *   Array key for sorting.
   *
   * @return array
   *   Sorted array.
   */
  public function sortByArrayKey(array $array_to_sort, $key) {
    uasort($array_to_sort, function ($a, $b) use ($key) {
      return strnatcmp($a[$key], $b[$key]);
    });
    return $array_to_sort;
  }

  /**
   * Get main information about the company.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $cid
   *   Array key for sorting.
   *
   * @return array
   *   Information about the company.
   */
  private function getCompanyData($options, $cid) {
    $qid = 'pmmi_psdata_company_real';
    $data_item = $options;
    $this->processQueue($qid, $data_item);
    return $this->cache->get($cid)->data;
  }

  /**
   * Get main information for the company staff section.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $cid
   *   Cache ID.
   *
   * @return array
   *   Information about the company staff.
   */
  private function getCompanyStaffData($options, $cid) {
    $qid = 'pmmi_psdata_staff_real';
    $data_item = $options;
    $this->processQueue($qid, $data_item);
    return $this->cache->get($cid)->data;
  }

  /**
   * Helper function for building Cache ID.
   *
   * @param object $options
   *   An object representing the current block settings.
   * @param string $type
   *   The type of cache ID.
   *
   * @return string
   *   Cache ID.
   */
  public function buildCid($options, $type) {
    $cid = '';
    $id = $options->id;
    switch ($type) {
      case 'main':
        if ($options->type == 'committee') {
          $cid = $this->provider . ':committee_' . $id;
        }
        else {
          $cid = $this->provider . ':' . $options->uuid;
        }
        break;

      case 'company':
        $country = '_' . strtolower($options->data['company']['country_code']);
        $cid = $this->provider . ':' . $type . '_' . $id . $country;
        break;

      case 'staff':
        $method = $options->data['company']['method'];
        $company_sec_comm = array_map('strtolower', $options->data['company']['comm_empl']);
        $staff_sec_comm = array_map('strtolower', $options->data['staff']['comm_empl']);
        $communications = array_unique(array_merge($company_sec_comm, $staff_sec_comm));
        $comm_str = implode('_', $communications);
        $cid = $this->provider . ':' . $type . '_' . $method . '_' . $comm_str . '_' . $id;
        break;

    }
    return $cid;
  }

  /**
   * Process the queue.
   *
   * @param string $qid
   *   The queue ID.
   * @param object $data_item
   *   The item to process.
   */
  private function processQueue($qid, $data_item) {
    $queue = $this->queue->get($qid);
    $queue->createItem($data_item);
    $queue_worker = $this->queueManager->createInstance($qid);
    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        watchdog_exception('pmmi_psdata', $e);
      }
    }
  }

}
