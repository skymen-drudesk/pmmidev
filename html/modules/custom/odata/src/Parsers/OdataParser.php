<?php

namespace Drupal\odata\Parsers;

use DOMDocument;
use DOMXPath;

/**
 * Class OdataParser.
 *
 * @package Drupal\odata
 */
class OdataParser implements OdataParserInterface {

  protected $domDocument;
  protected $xmlPath;
  protected $nameSpace;
  protected $EntityTypes;
  protected $ComplexTypes;

  /**
   * Constructs an OdataParser.
   *
   * @param string $xml
   *   A string containing a response from an oData request.
   */
  public function __construct($xml) {

    $this->domDocument = new DOMDocument();
    $status = $this->domDocument->loadXML($xml);

    $this->xmlPath = new DomXpath($this->domDocument);
    $this->xmlPath->registerNamespace('m', $this->domDocument->getElementsByTagName("Schema")->item(0)->getAttribute('xmlns'));
  }

  /**
   * Gets the Entity Types that are defined by this parser.
   *
   * @return array
   *   An array of mappings keyed [Name] => Name.
   */
  public function getEntityTypes() {

    $this->entity_types = array();

    foreach ($this->domDocument->getElementsByTagName("EntitySet") as $node) {
      $entity = explode(".", $node->getAttribute("EntityType"));
      $this->entity_types[$node->getAttribute("Name")] = end($entity);
    }

    return $this->entity_types;
  }

  /**
   * Gets the Entity Types that are defined by this parser.
   *
   * @param string $name
   *   Name for entity to find properties.
   *
   * @return array
   *   An array of mappings.
   */
  public function getPropertiesPerEntity($name = NULL) {

    if (is_null($name)) {
      $entity = $this->xmlPath->query("//m:EntityType");
    }
    else {
      $entity = $this->xmlPath->query("//m:EntityType[@Name='$name']");
    }

    $this->getComplexTypes();
    $properties = array();

    foreach ($entity as $property) {
      $property_name = array_search($property->getAttribute("Name"), $this->entity_types);
      foreach ($property->getElementsByTagName("Property") as $records) {
        if ($records->getAttribute("Name")) {
          $is_complex = array_key_exists($records->getAttribute("Name"), $this->ComplexTypes) ? TRUE : FALSE;
          $properties[$property_name][$records->getAttribute("Name")] = array(
            'Name' => $records->getAttribute("Name"),
            'Type' => $is_complex ? 'ComplexType' : $records->getAttribute("Type"),
            'Null' => $is_complex ? FALSE : $records->getAttribute("Nullable"),
            'Complex' => $is_complex ? $this->ComplexTypes[$records->getAttribute("Name")] : FALSE,
          );
        }
      }
    }
    return $properties;
  }

  /**
   * Checks if current schema contains complex types.
   *
   * @return array
   *   An associative array with the schema of the complex field.
   */
  public function getComplexTypes() {
    $complex_types = array();
    $complexfield = $this->xmlPath->evaluate("//m:ComplexType");
    if ($complexfield) {
      foreach ($complexfield as $property) {
        foreach ($property->getElementsByTagName("Property") as $records) {
          $complex_types[$property->getAttribute("Name")][$records->getAttribute("Name")] = array(
            'Name' => $records->getAttribute("Name"),
            'Type' => $records->getAttribute("Type"),
          );
        }
      }
    }
    $this->ComplexTypes = $complex_types;
  }

}
