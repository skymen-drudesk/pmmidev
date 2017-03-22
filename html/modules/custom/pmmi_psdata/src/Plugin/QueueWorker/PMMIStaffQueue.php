<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;

use Drupal\pmmi_psdata\Plugin\QueueWorker\PMMIBaseDataQueue;

/**
 * Updates a user's data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_staff",
 *   title = @Translation("Update Staff Pages"),
 *   cron = {"time" = 60}
 * )
 */
class PMMIStaffQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
//    $this->handleItem('users', $data);
  }

  /**
   * Get Committee Data.
   *
   * @param string $id
   *   The ID of requested committee.
   *
   * @return array
   *   The array of data.
   */
  protected function getStaffData($id) {
    $data = NULL;
    // CustomerInfos?$filter=CustomerClassCode eq 'STAFF'
    $query = [
      '$filter' => "CustomerClassCode eq '$id'",
    ];
    $request_options = $this->buildGetRequest('CustomerInfos', $query);

    if ($staff_data = $this->handleRequest($request_options)) {
      foreach ($staff_data as $customer) {
        $last_first_name = $customer->CommitteeMemberLastFirstName;
        $member_id = $customer->MemberMasterCustomer;
        $member_sub_id = $customer->MemberSubCustomer;
        $position = $customer->PositionCodeDescriptionDisplay;

        $data[$position][$last_first_name] = [
          'label_name' => $customer->CommitteeMemberLabelName,
          'end_date' => $this->formatDate($customer->EndDate, 'Y'),
          'member_id' => $member_id,
          'company_id' => $customer->RepresentingMasterCustomer,
          'company_name' => $customer->RepresentingLabelName,
        ];
        $member_ids[] = $member_id;
        if ($job_title = $this->getMemberJobTitle($member_id, $member_sub_id)) {
          $data[$position][$last_first_name]['job_title'] = $job_title;
        }
        $this->sort($data);
      }

    }
    else {
      $this->ssoHelper->log("Error with request to get Data Service Committee Members.");
    }
    return $data;
  }

}
