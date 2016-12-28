<?php

namespace Drupal\pmmi_fields\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Class VideoFieldAjax.
 *
 * @package Drupal\pmmi_fields\Controller
 */
class VideoFieldAjax extends ControllerBase {

  /**
   * Replace main video on Video CT.
   */
  public function replaceVideo($node) {
    $response = new AjaxResponse();
    $entity = Node::load($node);

    if ($entity->access('view')) {
      $rendered_field = $entity->field_video->view('full');
      $rendered_field['#prefix'] = '<div class="video-container">';
      $rendered_field['#suffix'] = '</div>';
      $video = render($rendered_field);
      $response->addCommand(new ReplaceCommand('.video-container', $video));
    }
    return $response;
  }

}
