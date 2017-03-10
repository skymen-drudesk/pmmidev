<?php

namespace Drupal\pmmi\WebformElementViews;

use Drupal\webform\WebformElementInterface;
use Drupal\webform_views\WebformElementViews\WebformElementViewsAbstract;

/**
 * Webform views handler for boolean webform elements.
 */
class WebformBooleanViews extends WebformElementViewsAbstract {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element) {
    $views_data = parent::getElementViewsData($element);

    $views_data['filter'] = [
      'id' => 'webform_submission_boolean_filter',
      'real field' => 'value',
    ];

    return $views_data;
  }

}
