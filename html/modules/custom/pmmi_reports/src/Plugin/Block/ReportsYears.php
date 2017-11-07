<?php

namespace Drupal\pmmi_reports\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\pmmi_reports\Plugin\QueueWorker\PMMIReportsQueue;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'ReportsYears' block.
 *
 * @Block(
 *  id = "reports_years",
 *  admin_label = @Translation("Reports archives by year"),
 * )
 */
class ReportsYears extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'personify_class' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['personify_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify Class'),
      '#description' => $this->t('Personify category class, i.e. <em>BENCHMARKING, ECONOMIC-TRENDS, INDUSTRY-RPTS, INTL-RESEARCH</em>.'),
      '#default_value' => $this->configuration['personify_class'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['personify_class'] = $form_state->getValue('personify_class');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Get term id by personify product class.
    $tid = PMMIReportsQueue::getTermIdByProductClass($this->configuration['personify_class']);
    if (!$tid) {
      return $build;
    }
    $term = Term::load($tid);
    if ($years = $this->getYearsList($tid)) {
      $items = [];
      $current_path = \Drupal::service('path.current')->getPath();
      foreach ($years as $year) {
        $url = Url::fromUserInput($current_path, ['query' => ['year' => $year]]);
        $items[] = Link::fromTextAndUrl($term->getName() . ' ' . $year, $url);
      }

      $build['years'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];

    }

    return $build;
  }

  /**
   * Get years list for selected personify class.
   */
  protected function getYearsList($tid) {
    $years = [];

    $connection = Database::getConnection();
    // Select all dates for reports with particular personify class.
    $query = $connection->select('node__field_product_status_date', 'date_table')
      ->fields('date_table', ['field_product_status_date_value', 'entity_id'])
      ->condition('date_table.bundle', 'report');
    $query->join('node__field_category', 'cat_table', 'cat_table.entity_id=date_table.entity_id');
    $query->condition('cat_table.field_category_target_id', $tid);
    $results = $query->execute()->fetchAll();

    // Sort all available years.
    foreach ($results as $field) {
      $timestamp = strtotime($field->field_product_status_date_value);
      $year = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y');
      $years[$year] = $year;
    }
    krsort($years);

    return $years;
  }

}
