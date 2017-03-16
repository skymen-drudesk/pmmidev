<?php

namespace Drupal\pmmi_sales_agent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Sales agent user stat entities.
 *
 * @ingroup pmmi_sales_agent
 */
class SADUserStatListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Sales agent user stat ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_sales_agent\Entity\SADUserStat */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.sad_user_stat.edit_form', array(
          'sad_user_stat' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
