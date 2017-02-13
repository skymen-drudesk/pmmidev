<?php

namespace Drupal\pmmi_personify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Client\Curl\Client;
use Mekras\Atom\Document\FeedDocument;
use Mekras\OData\Client\OData;
use Mekras\OData\Client\Service as oDataService;
use Mekras\OData\Client\URI\Uri as oDataUri;
use Mekras\OData\Client\URI\Filter as F;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'PMMICommitteesBlock' block.
 *
 * @Block(
 *  id = "pmmi_committees_block",
 *  admin_label = @Translation("PMMI Committees block"),
 * )
 */
class PMMICommitteesBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'constituent_id' => $this->t(''),
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['constituent_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Constituent ID'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['constituent_id'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['constituent_id'] = $form_state->getValue('constituent_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    /** @var \Http\Client\Curl\Client $http_client */
    $http_client = HttpClientDiscovery::find();
//    $http_client = new Client(NULL, NULL, ['CURLOPT_HTTPAUTH' => 'CURLAUTH_BASIC', 'CURLOPT_USERPWD' => "SUMMIT:pmg@123"]);
//    $http_client->options = ['CURLOPT_HTTPAUTH' => 'CURLAUTH_BASIC', 'CURLOPT_USERPWD' => "SUMMIT:pmg@123"];
    /** @var \Http\Message\MessageFactory\GuzzleMessageFactory $request_factory */
    $request_factory = MessageFactoryDiscovery::find();

    $config = \Drupal::config('pmmi_personify.settings');

    $service = new oDataService($config->get('endpoint'), $http_client, $request_factory);
    $constituent_id = $this->configuration['constituent_id'];
    $uri = new oDataUri();
    $uri->collection('CommitteeMembers');
    $uri->options()
      ->filter(F::eq("CommitteeMasterCustomer", "'C0000010'"));
//      ->top(5);
//    $document = $service->sendRequest(OData::GET, $uri);

//    if (!$document instanceof FeedDocument) {
//
//      $build['pmmi_committees_block_constituent_id']['#markup'] = '<p>No Data</p>';
//    }
//    else {
//      $entries = $document->getFeed()->getEntries();
//      foreach ($entries as $entry) {
////        printf("Id: %s\nRelease: %s\n", $entry['ID'], $entry['Price']);
//      }

      $build['pmmi_committees_block_constituent_id']['#markup'] = '<p>' . 'No Data' . '</p>';

//    }

    return $build;
  }

}
