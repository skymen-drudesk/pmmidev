<?php

namespace Drupal\pmmi_personify_sso\Authentication;

use Drupal\user\UserInterface;

interface TokenAuthUserInterface extends \IteratorAggregate, UserInterface {

  /**
   * Get the token.
   *
   * @return \Drupal\pmmi_personify_sso\Entity\PMMIPersonifySSOTokenInterface
   *   The provided OAuth2 token.
   */
  public function getToken();

}
