<?php

namespace Drupal\pmmi_personify_sso\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining Access Token entities.
 *
 * @ingroup pmmi_personify_sso
 */
interface PMMIPersonifySSOTokenInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Revoke a token.
   */
  public function revoke();

  /**
   * Check if the token was revoked.
   *
   * @return bool
   *   TRUE if the token is revoked. FALSE otherwise.
   */
  public function isRevoked();

}
