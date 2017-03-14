<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for Personify Company.
 *
 * This extends the base storage class, adding required special handling for
 * Personify Company entities.
 */
class PMMIPersonifyCompanyStorage extends SqlContentEntityStorage implements PMMIPersonifyCompanyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getExistCompanyByPersonifyId(array $ids) {
    $query = $this->database->select($this->getBaseTable(), 'pc');
    $query->fields('pc', ['id', 'personify_id']);
    $query->condition('pc.personify_id', $ids, 'IN');
    return $query->execute()->fetchAllKeyed();
  }

}
