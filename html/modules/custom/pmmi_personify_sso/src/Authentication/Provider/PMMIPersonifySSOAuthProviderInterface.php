<?php

namespace Drupal\pmmi_personify_sso\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
interface PMMIPersonifySSOAuthProviderInterface extends AuthenticationProviderInterface {


  /**
   * Gets the access token from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if there is an access token. FALSE otherwise.
   */
  public static function hasTokenValue(Request $request);

}
