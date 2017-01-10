<?php
//
//namespace Drupal\audience_select\Plugin\DataType;
//
//use Drupal\Core\TypedData\TypedData;
//
///**
// * The "audience" data type.
// *
// * The "audience" data type does not implement a list or complex data interface, nor
// * is it mappable to any primitive type. Thus, it may contain any PHP data for
// * which no further metadata is available.
// *
// * @DataType(
// *   id = "audience",
// *   label = @Translation("Audience")
// * )
// */
//class Audience extends TypedData {
//
//  /**
//   * The data value.
//   *
//   * @var string
//   */
//  protected $value;
//
//  /**
//   * Overrides TypedData::getValue().
//   *
//   * @return \Drupal\audience_select\Service\AudienceManager|null
//   */
//  public function getValue() {
//    if (!isset($this->value)) {
//      $this->value = \Drupal::service('audience_select.audience_manager')->getCurrentAudience();
//    }
//    return $this->value;
//  }
//}
