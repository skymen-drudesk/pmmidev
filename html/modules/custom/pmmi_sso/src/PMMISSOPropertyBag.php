<?php

namespace Drupal\pmmi_sso;

/**
 * Class PMMISSOPropertyBag.
 */
class PMMISSOPropertyBag {

  /**
   * The username of the PMMI SSO user.
   *
   * @var string
   */
  protected $username;

  /**
   * The proxy granting token, if supplied.
   *
   * @var string
   */
  protected $pgt;

  /**
   * An array containing attributes returned from the server.
   *
   * @var array
   */
  protected $attributes;

  /**
   * Contructor.
   *
   * @param string $user
   *   The username of the PMMI SSO user.
   */
  public function __construct($user) {
    $this->username = $user;
  }

  /**
   * Username property setter.
   *
   * @param string $user
   *   The new username.
   */
  public function setUsername($user) {
    $this->username = $user;
  }

  /**
   * Proxy granting token property setter.
   *
   * @param string $token
   *   The token to set as pgt.
   */
  public function setPgt($token) {
    $this->pgt = $token;
  }

  /**
   * Attributes property setter.
   *
   * @param array $sso_attributes
   *   An associative array containing attribute names as keys.
   */
  public function setAttributes(array $sso_attributes) {
    $this->attributes = $sso_attributes;
  }

  /**
   * Username property getter.
   *
   * @return string
   *   The username property.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Proxy granting token getter.
   *
   * @return string
   *   The pgt property.
   */
  public function getPgt() {
    return $this->pgt;
  }

  /**
   * PMMI SSO attributes getter.
   *
   * @return array
   *   The attributes property.
   */
  public function getAttributes() {
    return $this->attributes;
  }

}
