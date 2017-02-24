<?php

namespace Drupal\pmmi_sso\Service;

use Drupal\pmmi_sso\Exception\PMMISSOValidateException;
use Drupal\pmmi_sso\Parsers\PMMISSOXmlParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\pmmi_sso\PMMISSOPropertyBag;

/**
 * Class PMMISSOValidator.
 */
class PMMISSOValidator {

  /**
   * Stores the Guzzle HTTP client used when validating service tickets.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Stores PMMISSO helper.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * Constructor.
   *
   * @param Client $http_client
   *   The HTTP Client library.
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   */
  public function __construct(Client $http_client, PMMISSOHelper $sso_helper) {
    $this->httpClient = $http_client;
    $this->ssoHelper = $sso_helper;
  }

  /**
   * Validate the service token parameter present in the request.
   *
   * This method will return the username of the user if valid, and raise an
   * exception if the ticket is not found or not valid.
   *
   * @param string $token
   *   The PMMI SSO authentication ticket to validate.
   * @param array $service_params
   *   An array of query string parameters to add to the service URL.
   *
   * @return PMMISSOPropertyBag
   *   Contains user info from the PMMI SSO server.
   *
   * @throws PMMISSOValidateException
   *   Thrown if there was a problem making the validation request or
   *   if there was a local configuration issue.
   */
  public function validateToken($token, $decrypted = FALSE) {
//    $options = array();
//    $options['timeout'] = $this->ssoHelper->getConnectionTimeout();

    if ($decrypted) {
      return new PMMISSOPropertyBag('');
    }
    else {
      $options = $this->ssoHelper->getServerValidateOptions($token);
      if ($options['decrypt']) {
        $this->ssoHelper->log('Attempting to validate service ticket using URL: ' . $options['uri']);
        try {
          $response = $this->httpClient->request('POST', $options['uri'], ['form_params' => $options['params']]);
          $response_data = $response->getBody()->getContents();
//          $response_data = $response->getBody()->__toString();
          $this->ssoHelper->log("Validation response received from PMMI SSO server: " . htmlspecialchars($response_data));
        }
        catch (RequestException $e) {
          throw new PMMISSOValidateException("Error with request to validate ticket: " . $e->getMessage());
        }
        return $this->validate($response_data);
      }
      else {
        throw new PMMISSOValidateException('Token do not decrypted!!!');
      }
    }
  }

  /**
   * Validation of a service ticket for Version 2 of the PMMI SSO protocol.
   *
   * @param string $data
   *   The raw validation response data from PMMI SSO server.
   *
   * @return PMMISSOPropertyBag
   *   Contains user info from the PMMI SSO server.
   *
   * @throws PMMISSOValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function validate($data) {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
//    $dom->encoding = "utf-8";
    $parser = new PMMISSOXmlParser();
    $parser->setData($data);
    $xml = $parser->setQuery('//Valid');

    if ($xml->length > 0 && $xml->item(0)->nodeValue == 'false') {
      $token = $parser->setQuery('//NewCustomerToken');
      $val = $token->item(0)->nodeValue;
//      if ($token->length > 0) {
//        $val =
//      }
    }
    // Suppress errors from this function, as we intend to throw our own
    // exception.
    if (@$dom->loadXML($data) === FALSE) {
      throw new PMMISSOValidateException("XML from PMMI SSO server is not valid.");
    }

    $validation_status = $dom->getElementsByTagName('Valid');
    if ($validation_status->length > 0 && $validation_status->item(0)->nodeValue == 'true') {
      $new_token = $dom->getElementsByTagName('NewCustomerToken');
      if ($new_token->length > 0) {
        $property_bag = $this->getPropertyBag($new_token->item(0));
      }
      else {
        // All responses should have either an authenticationFailure
        // or authenticationSuccess node.
        throw new PMMISSOValidateException('XML from PMMI SSO server is not valid. No new token exist.');
      }
    }
    else {
      // Failed validation and toss exception.
      throw new PMMISSOValidateException('Token does not valid.');
    }

    $success_elements = $dom->getElementsByTagName("authenticationSuccess");
    if ($success_elements->length === 0) {
      // All responses should have either an authenticationFailure
      // or authenticationSuccess node.
      throw new PMMISSOValidateException("XML from PMMI SSO server is not valid.");
    }

    return $property_bag;
  }

  /**
   * Build & send request for UserID and return PropertyBag.
   *
   * @param string $token
   *   The token of the PMMI SSO user.
   *
   * @return PMMISSOPropertyBag
   *   Contains user info from the PMMI SSO server.
   *
   * @throws PMMISSOValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function getPropertyBag($token) {
    $options = $this->ssoHelper->getServiceUrl();

    // There should only be one success element, grab it and extract username.
    $success_element = $success_elements->item(0);
    $user_element = $success_element->getElementsByTagName("user");
    if ($user_element->length == 0) {
      throw new PMMISSOValidateException("No user found in ticket validation response.");
    }
    $username = $user_element->item(0)->nodeValue;
    $this->ssoHelper->log("Extracted user: $username");
    $property_bag = new PMMISSOPropertyBag($username);

    // If the server provided any attributes, parse them out into the property
    // bag.
    $attribute_elements = $dom->getElementsByTagName("attributes");
    if ($attribute_elements->length > 0) {
      $property_bag->setAttributes($this->parseAttributes($attribute_elements));
    }
    return $property_bag;
  }

}
