<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

use Drupal\pmmi_psdata\Plugin\QueueWorker\PMMIBaseDataQueue;

/**
 * Updates a user's data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_staff",
 *   title = @Translation("Update Staff Data"),
 *   cron = {"time" = 60}
 * )
 */
class PMMIStaffQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $cid = $this->provider . ':staff_' . $item->data['company']['method'] . '_' . $item->id;
    $staff_data = $this->getStaffData($item);
    if ($staff_data) {
      $this->cache->set($cid, $staff_data);
    }
  }

  /**
   * Get Committee Data.
   *
   * @param object $item
   *   The ID of requested committee.
   *
   * @return array
   *   The array of data.
   */
  protected function getStaffData($item) {
    $data = NULL;
    $method = $item->data['company']['method'];
    $request_options = $this->buildStaffRequest($item, $method);
    if ($staff_data = $this->handleRequest($request_options)) {
      foreach ($staff_data as $customer) {
        $label_name = '';
        $first_name = '';
        $last_name = '';
        if ($method == 'code') {
          $member_id = $customer->MasterCustomerId;
          $member_sub_id = $customer->SubCustomerId;
          $label_name = $customer->LabelName;
          $first_name = $customer->FirstName;
          $last_name = $customer->LastName;
        }
        else {
          $member_id = $customer->RelatedMasterCustomerId;
          $member_sub_id = $customer->RelatedSubCustomerId;
        }
        $data[$member_id] = [
          'id' => $member_id,
          'sub_id' => $member_sub_id,
          'label_name' => $label_name,
          'first_name' => $first_name,
          'last_name' => $last_name,
        ];
      }
      if ($method != 'code') {
        $members_requests = $this->separateRequest(array_keys($data), 'info');
        if ($members_data = $this->handleAsyncRequests($members_requests)) {
          foreach ($members_data as $member) {
            $data[$member->MasterCustomerId]['label_name'] = $member->LabelName;
            $data[$member->MasterCustomerId]['first_name'] = $member->FirstName;
            $data[$member->MasterCustomerId]['last_name'] = $member->LastName;
          }
        }
      }
      $address_requests = $this->separateRequest(array_keys($data), 'address');
      if ($address_data = $this->handleAsyncRequests($address_requests)) {
        foreach ($address_data as $address) {
          $data[$address->MasterCustomerId]['country'] = $address->CountryCode;
          $data[$address->MasterCustomerId]['job_title'] = $address->JobTitle;
        }
      }
      $comm_requests = $this->separateRequest(
        array_keys($data),
        'communication',
        $item->data['staff']['comm_empl']
      );
      if ($comm_data = $this->handleAsyncRequests($comm_requests)) {
        foreach ($comm_data as $comm) {
          $type = strtolower($comm->CommTypeCodeString);
          $data[$comm->MasterCustomerId]['comm'][$type] = $comm->FormattedPhoneAddress;
        }
      }
    }
    else {
      $this->ssoHelper->log("Error with request to get Data Service information about Employees.");
    }
    return $data;
  }

  private function buildStaffRequest($item, $method) {
    $company_id = $item->data['company']['id'];
    $company_sub_id = $item->data['company']['sub_id'];
    switch ($item->data['company']['method']) {
      // By CustomerClassCode.
      case 'code':
        // /CustomerInfos?$filter=CustomerClassCode    eq 'STAFF' .
        $path = 'CustomerInfos';
        $filter = $this->addFilter(
          'eq',
          'CustomerClassCode',
          [$item->data['company']['method_data']],
          TRUE
        );
        $query = [
          '$filter' => $filter,
          '$select' => 'MasterCustomerId,SubCustomerId,FirstName,LastName,LabelName',
        ];
        break;

      // By CustomerInfos\Relationship.
      case 'ci_rel':
        // /CustomerInfos(MasterCustomerId='02010445',SubCustomerId=0)
        // /Relationships?$filter=ActiveFlag  eq true and FullTimeFlag eq true .
        $path = "CustomerInfos(MasterCustomerId='$company_id',SubCustomerId=$company_sub_id)/Relationships";
        $filter = $this->addFilter('eq', 'ActiveFlag', ['true'], TRUE, TRUE);
        $filter .= $this->addFilter('eq', 'FullTimeFlag', ['true'], FALSE, TRUE);
        $query = [
          '$filter' => $filter,
        ];
        break;
    }
    return $this->buildGetRequest($path, $query);
  }

}
