<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;

/**
 * Defines the UserRolesCacheContext service, for "per sso role" caching.
 *
 * Only use this cache context when checking explicitly for certain roles. Use
 * user.permissions for anything that checks permissions.
 *
 * Cache context ID: 'user.pmmi_sso_roles' (to vary by all roles of the current
 * user).
 * Calculated cache context ID: 'user.pmmi_sso_roles:%role', e.g.
 * 'user.roles:anonymous'
 * (to vary by the presence/absence of a specific role).
 */
class PMMISSORolesCacheContext extends UserCacheContextBase implements CalculatedCacheContextInterface {


  /**
   * The user data service.
   *
   * @var array;
   */
  protected $ssoRoles;

  /**
   * PMMISSORolesCacheContext constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(AccountInterface $user, UserDataInterface $user_data) {
    parent::__construct($user);
    $this->ssoRoles = $user_data->get('pmmi_sso', $user->id(), 'sso_roles');
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("User's SSO roles");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($role = NULL) {
    // User 1 does not actually have any special behavior for roles; this is
    // added as additional security and backwards compatibility protection for
    // SA-CORE-2015-002.
    // @todo Remove in Drupal 9.0.0.
    if ($this->user->isAnonymous()) {
      return 'anonymous';
    }
    if ($this->user->id() == 1) {
      return 'is-super-user';
    }
    if ($role === NULL) {
      return implode(',', $this->user->getRoles());
    }
    else {
      return (in_array($role, $this->user->getRoles()) ? '0' : '1');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($role = NULL) {
    return (new CacheableMetadata())->setCacheTags(['user:' . $this->user->id()]);
  }

}
