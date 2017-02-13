<?php

namespace Drupal\odata;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Component\Serialization\PhpSerialize;
use Drupal\serialization\Encoder\XmlEncoder;

/**
 * Class OdataManager.
 *
 * @package Drupal\odata
 */
class OdataManager implements OdataInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;
  /**
   * Drupal\serialization\Encoder\JsonEncoder definition.
   *
   * @var \Drupal\serialization\Encoder\JsonEncoder
   */
  protected $serializerEncoderJson;
  /**
   * Drupal\Component\Serialization\PhpSerialize definition.
   *
   * @var \Drupal\Component\Serialization\PhpSerialize
   */
  protected $serializationPhpserialize;
  /**
   * Drupal\serialization\Encoder\XmlEncoder definition.
   *
   * @var \Drupal\serialization\Encoder\XmlEncoder
   */
  protected $serializerEncoderXml;
  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack, JsonEncoder $serializer_encoder_json, PhpSerialize $serialization_phpserialize, XmlEncoder $serializer_encoder_xml) {
    $this->requestStack = $request_stack;
    $this->serializerEncoderJson = $serializer_encoder_json;
    $this->serializationPhpserialize = $serialization_phpserialize;
    $this->serializerEncoderXml = $serializer_encoder_xml;
  }

  public function getDataRequest(){
//    $this->requestStack->push()
  }
}
