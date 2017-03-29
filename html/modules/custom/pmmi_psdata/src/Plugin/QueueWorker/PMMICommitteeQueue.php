<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;


/**
 * Updates Personify Committee data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_committee",
 *   title = @Translation("Update Committee pages"),
 *   cron = {"time" = 60}
 * )
 */
class PMMICommitteeQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $cid = $this->provider . ':' . $item->type . '_' . $item->id;
    $data = $this->getCommitteeData($item->id);
    if ($data) {
      $this->cache->set($cid, $data);
    }
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
  protected function getCommitteeData($id) {
    $data = NULL;
    $date = new \DateTime();
    $query = [
      '$filter' => "CommitteeMasterCustomer eq '$id' and " .
        "ParticipationStatusCodeString eq 'ACTIVE' and EndDate ge datetime'" .
        $date->format('Y-m-d') . "'",
    ];
    $request_options = $this->buildGetRequest('CommitteeMembers', $query);

    if ($committee_data = $this->handleRequest($request_options)) {
      foreach ($committee_data as $customer) {
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
