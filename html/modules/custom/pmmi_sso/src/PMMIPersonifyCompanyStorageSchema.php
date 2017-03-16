<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the Personify Company schema handler.
 */
class PMMIPersonifyCompanyStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);
    $schema['pmmi_personify_company']['unique keys'] += array('personify_id' => array('personify_id'));
    $schema['pmmi_personify_company']['indexes'] += array(
      'pmmi_personify_company__mapping' => array('id', 'personify_id'),
    );
    return $schema;
  }

}
