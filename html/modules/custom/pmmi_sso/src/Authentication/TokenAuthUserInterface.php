<?php

namespace Drupal\pmmi_sso\Authentication;

use Drupal\user\UserInterface;

interface TokenAuthUserInterface extends \IteratorAggregate, UserInterface {

  /**
   * Get the token.
   *
   * @return \Drupal\pmmi_sso\Entity\PMMISSOTokenInterface
   *   The provided OAuth2 token.
   */
  public function getToken();

}
