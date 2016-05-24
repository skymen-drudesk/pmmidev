<?php

namespace Drupal\audience_select\Service;

class AudienceHandling {
  private $configFactory;

  public function __construct($configFactory) {
    $this->configFactory = $configFactory;
  }

  public function getKeyedAudiences() {

    if ($number == null) {
      $config = $this->configFactory->get('beignet.settings');

      $number = $config->get('beignet_default_number');
    }

    $flavors = ['chocolate', 'strawberry', 'bananas', 'delicious', 'yum'];
    $beignets = [];
    for ($i = 0; $i < $number; $i++) {
      $key = array_rand($flavors);
      $flavor = $flavors[$key];
      $beignets[] = $flavor;
    }

    return $beignets;
  }
}
