<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

/**
 * Updates a user's data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_company",
 *   title = @Translation("Update Company Data"),
 *   cron = {"time" = 60}
 * )
 */
class PMMICompanyQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $cid = $this->dataCollector->buildCid($item, 'company');
    $data = $this->getCompanyData($item);
    if ($data) {
      $this->dataCollector->invalidateTags([$cid]);
      $expiration_time = REQUEST_TIME + $item->data['company']['expiration'];
      $this->cache->set($cid, $data, $expiration_time);
    }
  }

  /**
   * Get main information about the company.
   *
   * @param object $item
   *   The type of requested data.
   *
   * @return array
   *   Information about the company.
   */
  private function getCompanyData($item) {
    $company_data = [];
    $company_id = $item->data['company']['id'];
    $company_sub_id = $item->data['company']['sub_id'];
    // Example path: CustomerInfos(MasterCustomerId='00094039',SubCustomerId=0)
    // /Addresses?$filter=AddressStatusCode eq 'GOOD'&$select=JobTitle .
    $filter = $this->addFilter('eq', 'AddressStatusCode', ['GOOD'], TRUE);
    $filter .= $this->addFilter('eq', 'AddressTypeCodeString', $item->data['company']['address']);
    $filter .= $this->addFilter('eq', 'CountryCode', [$item->data['company']['country_code']]);
    $path_element = "CustomerInfos(MasterCustomerId='" . $company_id .
      "',SubCustomerId=" . $company_sub_id . ")/Addresses";
    $query = [
      '$filter' => $filter,
// @todo: Select only needed fields
//        '$select' => 'JobTitle',
    ];
    $request_options = $this->buildGetRequest($path_element, $query);
    if ($addresses = $this->handleRequest($request_options)) {
      foreach ($addresses as $address) {
        $company_data[$company_id][$address->CountryCode] = [
          'company_name' => $address->CompanyName,
          'city' => $address->City,
          'address_1' => $address->Address1,
          'address_2' => $address->Address2,
          'address_3' => $address->Address3,
          'address_4' => $address->Address4,
          'postal_code' => $address->PostalCode,
          'state' => $address->State,
          'formatted_postal' => $address->FormattedCityStatePostal,
        ];
      }
      if ($comm_data = $this->getCustomerInfo($company_id, $company_sub_id, $item, 'communications', 'company')) {
        foreach ($comm_data as $comm) {
          $type = strtolower($comm->CommTypeCodeString);
          $location = strtolower($comm->CommLocationCodeString);
          $company_data[$company_id][$comm->CountryCode]['comm'][$location][$type] = $comm->FormattedPhoneAddress;
        }
      }
    }
    return $company_data;
  }

}
