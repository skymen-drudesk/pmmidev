<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Personify company entities.
 *
 * @ingroup pmmi_sso
 */
class PMMIPersonifyCompanyListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['personify_id'] = $this->t('Personify company ID');
    $header['name'] = $this->t('Name');
    $header['code'] = $this->t('Customer Class Code');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_sso\Entity\PMMIPersonifyCompany */
    $row['id'] = $entity->id();
    $row['personify_id'] = $entity->getPersonifyId();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.pmmi_personify_company.edit_form', array(
          'pmmi_personify_company' => $entity->id(),
        )
      )
    );
    $row['code'] = $entity->getCode();
    return $row + parent::buildRow($entity);
  }

}
