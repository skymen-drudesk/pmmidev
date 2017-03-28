<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

use Drupal\pmmi_psdata\Plugin\QueueWorker\PMMIBaseDataQueue;

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
    $country = '_' . strtolower($item->data['company']['country_code']);
    $cid = $this->provider . ':' . $item->type . '_' . $item->id . $country;
    $data = $this->getCompanyData($item);
//    $staff_data = $this->getStaffData($item->id);
//    $data = $this->sort($company_data, $staff_data);

    if ($data) {
      $this->cache->set($cid, $data);
    }
  }

  /**
   * Get member Job Title.
   *
   * @param string $company_ids
   *   The MemberMasterCustomer IDs.
   * @param int $member_sub_id
   *   The MemberSubCustomer ID.
   *
   * @return string
   *   The job title.
   */
  public function getCompanyData($item) {
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

//  /**
//   * Helper function for sorting data.
//   *
//   * @param array $data
//   *   The Data array.
//   *
//   * @return array
//   *   The Data array.
//   */
//  protected function sort(array $company_data, array $staff_data) {
//
//    foreach ($data as &$value) {
//      ksort($value);
//    }
//    return ksort($data);
//  }




//  /**
//   * Get Customer Info.
//   *
//   * @param string $member_id
//   *   The MemberMasterCustomer ID.
//   * @param int $member_sub_id
//   *   The MemberSubCustomer ID.
//   * @param string $collection
//   *   Requested collection.
//   *
//   * @return string
//   *   The job title.
//   */
//  protected function getCustomerInfo($member_id, $member_sub_id, $collection, $type = 'member') {
//    switch ($collection) {
//      case 'addresses':
//        // Example path: /CustomerInfos(MasterCustomerId='00094039',
//        // SubCustomerId=0)/Addresses?$filter=AddressStatusCode eq 'GOOD'
//        // and PrioritySeq eq 0&$select=JobTitle,CountryCode .
//        $path_element = "CustomerInfos(MasterCustomerId='" . $member_id .
//          "',SubCustomerId=" . $member_sub_id . ")/Addresses";
//        $query = [
//          '$filter' => "AddressStatusCode eq 'GOOD' and PrioritySeq eq 0",
//          '$select' => 'JobTitle,CountryCode',
//        ];
//        break;
//
//      case 'communications':
//        // Example path: /CustomerInfos(MasterCustomerId='00094039',
//        // SubCustomerId=0)/Communications?$filter=CommLocationCodeString eq
//        // 'WORK' and (CommTypeCodeString eq 'EMAIL' or CommTypeCodeString eq
//        // 'PHONE' )&$select=CommTypeCodeString,FormattedPhoneAddress .
//        $path_element = "CustomerInfos(MasterCustomerId='" . $member_id .
//          "',SubCustomerId=" . $member_sub_id . ")/Communications";
//        $query = [
//          '$select' => 'CommTypeCodeString,FormattedPhoneAddress',
//        ];
//        if ($type == 'company') {
//          $query['$filter'] = "(CommLocationCodeString eq 'WORK' or CommLocationCodeString eq 'WORK2') and (CommTypeCodeString eq 'EMAIL' or CommTypeCodeString eq 'PHONE' or CommTypeCodeString eq 'FAX')";
//        }
//        else {
//          $query['$filter'] = "CommLocationCodeString eq 'WORK' and (CommTypeCodeString eq 'EMAIL' or CommTypeCodeString eq 'PHONE')";
//        }
//        break;
//    }
//
//    $request_options = $this->buildGetRequest($path_element, $query);
//    if ($item = $this->handleRequest($request_options)) {
//      return $item;
//    }
//    return NULL;
//  }

}
