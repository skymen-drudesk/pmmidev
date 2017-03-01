<?php

namespace Drupal\pmmi_sso;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Access Token entity.
 *
 * @see \Drupal\pmmi_sso\Entity\AccessToken.
 */
class AccessTokenAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($admin_permission = $this->entityType->getAdminPermission()) {
      return AccessResult::allowedIfHasPermission($account, $admin_permission);
    }
    // Permissions only apply to own entities.
    $is_owner = $account->id() == $entity->get('uid')->target_id;
    $is_owner_access = AccessResult::allowedIf($is_owner)
      ->addCacheableDependency($entity);
    if (!in_array($operation, ['view', 'update', 'delete'])) {
      return AccessResult::neutral();
    }

    return $is_owner_access->andIf(AccessResult::allowedIfHasPermission(
      $account,
      sprintf('%s own pmmi_sso entities', $operation)
    ));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add access token entities');
  }

}
