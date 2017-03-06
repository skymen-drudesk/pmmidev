<?php

namespace Drupal\pmmi_address\Plugin\Validation\Constraint;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraintValidator;

/**
 * Validates the address format constraint.
 */
class PmmiAddressFormatConstraintValidator extends AddressFormatConstraintValidator {

  /**
   * {@inheritdoc}
   */
  protected function addViolation($field, $message, $invalid_value, AddressFormat $address_format) {
    $bundle = $this->context->getObject()->getDataDefinition()->getFieldDefinition()->getTargetBundle();
    if ($bundle == 'event' && $message == '@name field is required.') {
      return FALSE;
    }
    parent::addViolation($field, $message, $invalid_value, $address_format);
  }

}
