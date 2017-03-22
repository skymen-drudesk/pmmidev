<?php

namespace Drupal\pmmi_psdata\Plugin\QueueWorker;


/**
 * Updates a user's data.
 *
 * @QueueWorker(
 *   id = "pmmi_psdata_customer_job_title",
 *   title = @Translation("Update Customer Job Title"),
 *   cron = {"time" = 60}
 * )
 */
class PMMICustomerJobTitleQueue extends PMMIBaseDataQueue {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->handleItem('users', $data);
  }

}
