<?php

namespace Drupal\pmmi_sso;

/**
 * Class PMMISSORedirectData.
 */
class PMMISSORedirectData {

  /**
   * Parameters used when redirecting to the SSO server.
   *
   * @var array
   */
  protected $redirectParameters = [];

  /**
   * Parameters to add to the service_url when requesting redirect.
   *
   * @var array
   */
  protected $serviceParameters = [];

  /**
   * Indicates whether the redirect will occur.
   *
   * @var bool
   */
  protected $willRedirect = TRUE;

  /**
   * Indicates whether the redirect response can be cached.
   *
   * @var bool
   */
  protected $isCacheable = FALSE;

  /**
   * Cache tags to apply to cachable redirect responses.
   *
   * @var array
   */
  protected $cacheTags = ['config:pmmi_sso.settings'];

  /**
   * Cache contexts to apply to cacheable redirect responses.
   *
   * @var array
   */
  protected $cacheContexts = [];

  /**
   * PMMISSORedirectData constructor.
   *
   * @param array $service_parameters
   *   Default service parameters.
   * @param array $redirect_parameters
   *   Default redirect parameters.
   */
  public function __construct(array $service_parameters = [], array $redirect_parameters = []) {
    $this->serviceParameters = $service_parameters;
    $this->redirectParameters = $redirect_parameters;
  }

  /**
   * Set a redirection parameter.
   *
   * Sets a redirect parameter that will later be used in the redirection
   * request. NULL values will cause the parameter to be unset, but not
   * when they are required.
   *
   * @param string $key
   *   Key of parameter to set.
   * @param mixed $value
   *   Value of parramter to set.
   */
  public function setParameter($key, $value) {
    if (empty($value)) {
      unset($this->redirectParameters[$key]);
    }
    else {
      $this->redirectParameters[$key] = $value;
    }
  }

  /**
   * Returns the redirect parameter specified by $key.
   *
   * @param string $key
   *   Parameter to select.
   *
   * @return mixed
   *   Value of the parameter.
   */
  public function getParameter($key) {
    return isset($this->redirectParameters[$key]) ? $this->redirectParameters[$key] : NULL;
  }

  /**
   * Returns all parameters that will be used in redirect.
   *
   * @return array
   *   Array representation of all redirect parameters.
   */
  public function getAllParameters() {
    return $this->redirectParameters;
  }

  /**
   * Set a service paramater.
   *
   * @param string $key
   *   Service parameter to set.
   * @param mixed $value
   *   Value of service parameter to set.
   */
  public function setServiceParameter($key, $value) {
    if (empty($value)) {
      unset($this->serviceParameters[$key]);
    }
    else {
      $this->serviceParameters[$key] = $value;
    }
  }

  /**
   * Returns the redirect parameter specified by $key.
   *
   * @param string $key
   *   Parameter to select.
   *
   * @return string
   *   Value of the attribute.
   */
  public function getServiceParameter($key) {
    return isset($this->serviceParameters[$key]) ? $this->serviceParameters[$key] : NULL;
  }

  /**
   * Get all service parameters.
   *
   * @return array
   *   Array containing all service parameters.
   */
  public function getAllServiceParameters() {
    return $this->serviceParameters;
  }

  /**
   * Indicate that the redirect response is cacheable.
   *
   * @param bool $cacheable
   *   TRUE to set the redirect as cacheable, FALSE otherwise.
   */
  public function setIsCacheable($cacheable) {
    if ($cacheable) {
      $this->isCacheable = TRUE;
    }
    else {
      $this->isCacheable = FALSE;
    }
  }

  /**
   * Return if the redirect response is cacheable or not.
   *
   * @return bool
   *   TRUE if the redirect response is cacheable, FALSE otherwise.
   */
  public function getIsCacheable() {
    return $this->isCacheable;
  }

  /**
   * Check if a redirect is be allowed.
   *
   * @return bool
   *   TRUE implies that a redirectwill occur.
   *   FALSE implies that no redirect will occur.
   */
  public function willRedirect() {
    return $this->willRedirect;
  }

  /**
   * Force the redirection to occur (may still be prevented).
   */
  public function forceRedirection() {
    $this->willRedirect = TRUE;
  }

  /**
   * Prevent Redirection form occuring (may still be forced).
   */
  public function preventRedirection() {
    $this->willRedirect = FALSE;
  }

  /**
   * Set the cache tags that will be added to the redirect response.
   *
   * @param array $cache_tags
   *   The cache tags.
   */
  public function setCacheTags(array $cache_tags) {
    $this->cacheTags = $cache_tags;
  }

  /**
   * Get the cache tags for the redirect response.
   *
   * @return array
   *   The cache tags.
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * Set the cache contexts for the redirect response.
   *
   * @param array $cache_contexts
   *   The cache contexts.
   */
  public function setCacheContexts(array $cache_contexts) {
    $this->cacheContexts = $cache_contexts;
  }

  /**
   * Get the cache contexts for the redirect response.
   *
   * @return array
   *   The cache contexts.
   */
  public function getCacheContexts() {
    return $this->cacheContexts;
  }

}
