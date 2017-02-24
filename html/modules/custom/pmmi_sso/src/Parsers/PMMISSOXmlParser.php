<?php

namespace Drupal\pmmi_sso\Parsers;

use DOMDocument;
use DOMXPath;
use Drupal\pmmi_sso\Exception\PMMISSOValidateException;

/**
 * Class PMMISSOXmlParser.
 *
 * @package Drupal\pmmi_sso
 */
class PMMISSOXmlParser {

  /**
   * Stores database connection.
   *
   * @var \DOMDocument
   */
  protected $domDocument;

  /**
   * Stores database connection.
   *
   * @var \DOMXPath
   */
  protected $xmlPath;

  /**
   * Stores database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $nameSpace;

  /**
   * Constructs an PMMISSOXmlParser.
   */
  public function __construct() {
    $this->domDocument = new DOMDocument();
    $this->domDocument->preserveWhiteSpace = FALSE;
    $this->domDocument->encoding = "utf-8";
  }

  /**
   * Set Data PMMISSOXmlParser.
   */
  public function setData($xml) {
    // Suppress errors from this function, as we intend to throw our own
    // exception.
//    $status = $this->domDocument->loadXML($xml);
    if (@$this->domDocument->loadXML($xml) === FALSE) {
      throw new PMMISSOValidateException("XML from PMMI SSO server is not valid.");
    }
    $this->xmlPath = new DomXpath($this->domDocument);
    $dd = $this->xmlPath->query('valid');
    $dd1 = $this->xmlPath->query('SSOCustomerTokenIsValidResult/Valid');

//    $this->xmlPath->registerNamespace('m', $this->domDocument->getElementsByTagName("Schema")
//      ->item(0)
//      ->getAttribute('xmlns'));
    return $this;
  }

  public function setQuery($query) {
    return $this->xmlPath->query($query);
  }

  /**
   * Parse validate response.
   *
   * @return array
   *   An array of mappings values.
   */
  public function parseValidate() {
    $this->xmlPath->query('//m:V');
  }

}