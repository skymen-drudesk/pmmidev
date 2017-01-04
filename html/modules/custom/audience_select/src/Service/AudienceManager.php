<?php

/**
 * @file
 * Contains \Drupal\audience_select\Service
 */

namespace Drupal\audience_select\Service;

use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Provides functions to manage audience.
 *
 * @package Drupal\audience_select\Service
 */
class AudienceManager {

  /**
   * Turns audiences settings string into keyed array.
   *
   * @return array
   */
  public function getAllAudiences() {
    $config = \Drupal::config('audience_select.settings');
    $audiences = $config->get('audiences');
    $audiences = explode(PHP_EOL, $audiences);
    $keyed_audiences = [];

    foreach ($audiences as $audience) {
      $audience = rtrim($audience, ' \t\n\r\0\x0B|');
      $split = explode('|', $audience);
      $keyed_audiences[$split[0]] = [
        'gateway' => $split[1],
      ];
      if (isset($split[2]) && !empty($split[2])) {
        $keyed_audiences[$split[0]]['block'] = $split[2];
      }
      else {
        $keyed_audiences[$split[0]]['block'] = $split[1];
      }
    }

    return ($keyed_audiences);
  }

  /**
   * Get Gateway Url.
   *
   * @return string
   */
  public function getGateway() {
    $gateway_url = \Drupal::config('audience_select.settings')->get('gateway_url');
    if(!empty($gateway_url)){
      $gateway = $this->getUriAsDisplayableString($gateway_url);
    } else {
      $gateway = NULL;
    }
    return $gateway;
  }

  /**
   * Get all audiences data into keyed array.
   *
   * @return array
   */
  public function getData() {
    $config = \Drupal::config('audience_select.settings');
    $audiences = $config->get('map');
    foreach ($audiences as &$audience) {
      $audience['audience_redirect_url'] = !empty($audience['audience_redirect_url']) ? $this->getUriAsDisplayableString($audience['audience_redirect_url']) : NULL;
    }
    return $audiences;
  }

  /**
   * Get all audiences as keyed array (audience_id => audience_title).
   *
   * @return array
   */
  public function getOptionsList() {
    $audiences = self::getData();
    $options = [];
    foreach ($audiences as $id => $audience) {
      $options[$id] = $audience['audience_title'];
    }
    return $options;
  }

  public static function load($audience_id) {
    $config = \Drupal::config('audience_select.settings');
    $audiences = $config->get('map');
    return array_key_exists($audience_id, $audiences) ? $audiences[$audience_id] : NULL;
  }

  public function getGatewayAudiences() {
    $all_audiences = self::getAllAudiences();
    $audiences = [];

    foreach ($all_audiences as $key => $audience) {
      $audiences[$key] = $audience;
    }

    return $audiences;
  }

  public function getCurrentAudience() {
    $audience = isset($_COOKIE['audience_select_audience']) ? $_COOKIE['audience_select_audience'] : NULL;

    return $audience;
  }

  /**
   * Turns audiences settings string into keyed array.
   *
   * @return array
   */
  public function getUnselectedAudiences() {
    $audiences = self::getAllAudiences();
    $audience = self::getCurrentAudience();

    if ($audience !== NULL
      && !empty($audience)
      && array_key_exists($audience, $audiences)
    ) {
      unset($audiences[$audience]);
    }

    return ($audiences);
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *
   */
  public function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      $entity_manager = \Drupal::entityTypeManager();
      if ($entity_manager->getDefinition($entity_type, FALSE) && $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels(array($entity));
      }
    }

    return $displayable_string;
  }
}
