<?php

namespace Drupal\pmmi_psdata\Service;

use Drupal\Component\Utility\NestedArray;
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
   * Get Members Data.
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
//    if ($cache = $this->cache->get('sds')) {
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
    $this->cacheTagsInvalidator->invalidateTags([$cid]);
    return $this->cache->get($cid)->data;
  }

  /**
   * Build company data array.
   *
   * @param object $options
   *
   * @param $main_cid
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
      $this->cache->set($main_cid, $data);
    }
  }

  protected function sortCompanyData($options, $company_data, $staff_data) {
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

  public function filterCompanyStaff($array_to_filter, $filter_by) {
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


  public function filterAndSortCommunications(array $array_to_sort, $array_by_sort) {
    foreach ($array_to_sort as &$item) {
      if (!empty($item['comm'])) {
        $this->filterAndSortByArrayKeys($item['comm'], $array_by_sort);
      }
    }
    return $array_to_sort;
  }

  public function filterAndSortByArrayKeys(array &$array_to_sort, $array_by_sort) {
    $sort_keys = array_flip(array_map('strtolower', $array_by_sort));
    $filtered = array_intersect_key($array_to_sort, $sort_keys);
    $array_to_sort = array_replace($sort_keys, $filtered);
  }

  public function sortByArrayKey(array $array_to_sort, $key) {
    uasort($array_to_sort, function ($a, $b) use ($key) {
      return strnatcmp($a[$key], $b[$key]);
    });
    return $array_to_sort;
  }

  private function getCompanyData($options, $cid) {
    $qid = 'pmmi_psdata_company_real';
    $data_item = $options;
    $this->processQueue($qid, $data_item);
    return $this->cache->get($cid)->data;
  }

  private function getCompanyStaffData($options, $cid) {
    $qid = 'pmmi_psdata_staff_real';
    $data_item = $options;
    $this->processQueue($qid, $data_item);
    return $this->cache->get($cid)->data;
  }

  private function buildCid($options, $type) {
    $cid = '';
    switch ($type) {
      case 'main':
        if ($options->type == 'committee') {
          $cid = $this->provider . ':committee_' . $options->id;
        }
        else {
          $cid = $this->provider . ':' . $options->uuid;
        }
        break;

      case 'company':
        $country = '_' . strtolower($options->data['company']['country_code']);
        $cid = $this->provider . ':' . $type . '_' . $options->id . $country;
        break;

      case 'staff':
        $cid = $this->provider . ':' . $type . '_' . $options->data['company']['method'] . '_' . $options->id;
        break;

    }
    return $cid;
  }

  private function processQueue($qid, $data_item) {
    $queue = $this->queue->get($qid);
    $queue->createItem($data_item);
    $queue_worker = $this->queueManager->createInstance($qid);
    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      } catch (\Exception $e) {
        watchdog_exception('pmmi_psdata', $e);
      }
    }
  }

}
