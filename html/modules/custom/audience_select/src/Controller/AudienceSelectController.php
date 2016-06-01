<?php

/**
 * @file
 * Contains \Drupal\audience_select\Controller\AudienceSelectController
 */

namespace Drupal\audience_select\Controller;

use Drupal\Core\Url;
// Change following https://www.drupal.org/node/2457593
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for Audience select pages.
 */
class AudienceSelectController {
  
  public function content() {
    $audiences = $this->getAllAudiences();
    return [
      '#theme' => 'page__gateway',
      '#audiences' => $audiences,
    ];
  }

  /**
   * Turns audiences settings string into keyed array.
   *
   * @return array
   */
  public static function getAllAudiences() {
    $config = \Drupal::config('audience_select.settings');
    $audiences = $config->get('audiences');
    $audiences = explode(PHP_EOL, $audiences);
    $keyed_audiences = [];

    foreach ($audiences as $audience) {
      $audience = rtrim($audience, ' \t\0\x0B|');
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

    return($keyed_audiences);
  }

  /**
   * Turns audiences settings string into keyed array.
   *
   * @return array
   */
  public static function getUnselectedAudiences() {
    $audiences = self::getAllAudiences();
    $audience = isset($_COOKIE['audience_select_audience']) ? $_COOKIE['audience_select_audience'] : FALSE;

    if ($audience && array_key_exists($audience, $audiences)) unset($audiences[$audience]);

    return($audiences);
  }

  public static function getTemplatePath() {
    $module_path = drupal_get_path('module', 'audience_select');
    $config = \Drupal::config('audience_select.settings');
    $template = $config->get('template');
    $template = empty($template) ? $module_path . '/templates/page--gateway.html.twig' : $template;
    return $template;
  }
}
