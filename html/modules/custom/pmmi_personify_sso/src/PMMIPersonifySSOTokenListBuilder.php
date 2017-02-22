<?php

namespace Drupal\pmmi_personify_sso;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\user\RoleInterface;

/**
 * Defines a class to build a listing of Access Token entities.
 *
 * @ingroup pmmi_personify_sso
 */
class PMMIPersonifySSOTokenListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['user'] = $this->t('User');
    $header['name'] = $this->t('Token');
    $header['scopes'] = $this->t('Scopes');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pmmi_personify_sso\Entity\PMMIPersonifySSOToken */
    $row['id'] = $entity->id();
    $row['user'] = NULL;
    $row['name'] = $entity->toLink(sprintf('%s…', substr($entity->label(), 0, 10)));
    $row['scopes'] = NULL;
    if (($user = $entity->get('auth_user_id')) && $user->entity) {
      $row['user'] = $user->entity->toLink($user->entity->label());
    }
    if (($client = $entity->get('client')) && $client->entity) {
      $row['client'] = $client->entity->toLink($client->entity->label(), 'edit-form');
    }
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $scopes */
    if ($scopes = $entity->get('scopes')) {
      $row['scopes'] = implode(', ', array_map(function (RoleInterface $role) {
        return $role->label();
      }, $scopes->referencedEntities()));
    }

    return $row + parent::buildRow($entity);
  }

}
