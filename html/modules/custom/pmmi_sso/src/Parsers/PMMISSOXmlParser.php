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
    if (@$this->domDocument->loadXML($xml) === FALSE) {
      throw new PMMISSOValidateException("XML from PMMI SSO server is not valid.");
    }
    $this->xmlPath = new DOMXPath($this->domDocument);
    $rootNamespace = $this->domDocument->lookupNamespaceUri($this->domDocument->namespaceURI);
    $this->xmlPath->registerNamespace('m', $rootNamespace);
    return $this;
  }

  /**
   * Get NodeList by query for XML Document.
   *
   * @return \DOMNodeList
   *   The DOMNodeList class.
   */
  public function getNodeList($query) {
    return $this->xmlPath->query($query);
  }

  /**
   * Validate parameter.
   *
   * @param string $query
   *   The XPath query for XML Document.
   *
   * @return bool
   *   The validate variable.
   */
  public function validateBool($query) {
    $value = $this->xmlPath->query($query);
    if ($value->length > 0 && $value->item(0)->nodeValue == 'true') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get single value from XML Markup.
   *
   * @param string $query
   *   The XPath query for XML Document.
   *
   * @return string
   *   The value.
   *
   * @throws PMMISSOValidateException
   *   If one or more of the arguments are not valid.
   */
  public function getSingleValue($query) {
    $value = $this->xmlPath->query($query);
    if ($value->length == 0) {
      throw new PMMISSOValidateException("Response XML from PMMI SSO server is not valid.");
    }
    return $value->item(0)->nodeValue;
  }

  /**
   * Get single value from XML Markup.
   *
   * @param string $query
   *   The XPath query for XML Document.
   *
   * @return array
   *   The value.
   *
   * @throws PMMISSOValidateException
   *   If one or more of the arguments are not valid.
   */
  public function getMultiplyValues($query) {
    /** @var \DOMNodeList $value */
    $values = $this->xmlPath->query($query);
    if ($values->length == 0) {
      throw new PMMISSOValidateException("Response XML from PMMI SSO server is not valid.");
    }
    $result = array();
    /** @var \DOMElement $item */
    foreach ($values as $item) {
      $result[] = $item->nodeValue;
    }
    return $result;
  }

}
