<?php

namespace Drupal\odata\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Odata entity entities.
 */
interface OdataEntityInterface extends ConfigEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getEndpointUrl();

}
