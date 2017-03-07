<?php

namespace Drupal\pmmi_sales_agent;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Downloads quota entities.
 */
class SADDownloadsQuotaListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sad_downloads_quota_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['quota'] = $this->t('Quota');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\pmmi_sales_agent\SADDownloadsQuotaInterface $entity */
    $row['quota'] = $entity->getQuota();
    return $row + parent::buildRow($entity);
  }
}
