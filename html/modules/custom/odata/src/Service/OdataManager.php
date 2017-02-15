<?php

namespace Drupal\odata\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
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
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
  public function __construct(ClientInterface $http_client, RequestStack $request_stack, JsonEncoder $serializer_encoder_json, PhpSerialize $serialization_phpserialize, XmlEncoder $serializer_encoder_xml) {
    $this->httpClient = $http_client;
    $this->requestStack = $request_stack;
    $this->serializerEncoderJson = $serializer_encoder_json;
    $this->serializationPhpserialize = $serialization_phpserialize;
    $this->serializerEncoderXml = $serializer_encoder_xml;
  }

  /**
   * Executes an oData request.
   *
   * @param string $request
   *   A string containing a fully qualified URI.
   *
   * @param string $format
   *   In which format to request the data.
   *
   * @return object
   *   Either an array of data, or s string containing
   *   the response body that was received.
   * @todo refactor
   */
  public function getDataRequest($request_data, $format, $metadata = FALSE) {
    /** @var \Drupal\odata\Entity\OdataEntity $odata_entity */
    $odata_entity = $request_data->entity;
    if ($format == 'json') {
      $url = $odata_entity->getEndpointUrl();
//      $request_format = 'application/json';
//      $request_format = 'application/json;odata=verbose';
      $request_format = 'application/json;odata=light;q=1,application/json;odata=verbose;q=0.5';
    }
    elseif ($format == 'xml') {
      $url = $metadata ? $odata_entity->getEndpointUrl() . '/$metadata' : $odata_entity->getEndpointUrl();
      $request_format = 'application/xml';
    }
    else {
      drupal_set_message(t("Server message: No specified format for request"), 'error');
      return NULL;
    }
    /** @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    $options = [
      'headers' => [
        'Accept' => $request_format,
      ],
      'auth' => [$odata_entity->getUsername(), $odata_entity->getPassword()],
    ];
    try {
      $response = $client->request('GET', $url, $options);
      // Expected result.
      $data = $response->getBody();
      if ($format == 'json') {
        $json = Json::decode($response->getBody());
        // @todo
        if (isset($json['d']['results'])) {
          return $json['d']['results'];
        }

        if (isset($json['value'])) {
          return $json['value'];
        }
        return $json['d'];
      }
      return $response->getBody();
    }
    catch (RequestException $e) {
      watchdog_exception('odata', $e);
      drupal_set_message(t("Wrong response: @message", ['@message' => $e->getMessage()]), 'error');
      return NULL;
    }
  }

}
