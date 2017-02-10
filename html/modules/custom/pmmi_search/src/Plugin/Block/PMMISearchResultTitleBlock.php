<?php

namespace Drupal\pmmi_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Provides a 'PMMISearchResultTitleBlock' block.
 *
 * @Block(
 *  id = "pmmi_search_result_title_block",
 *  admin_label = @Translation("PMMI Search Result Title block"),
 *  category = @Translation("PMMI Search")
 * )
 */
class PMMISearchResultTitleBlock extends PMMISearchBlock implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'search_title' => '',
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['search_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Title'),
      '#description' => $this->t('Enter Search Title'),
      '#default_value' => $this->configuration['search_title'],
      '#required' => TRUE,
      '#weight' => '3',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['search_title'] = $form_state->getValue('search_title');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (!empty($this->configuration['search_path']) && !empty($this->configuration['search_identifier'])) {
      $search_path = Url::fromUri($this->configuration['search_path']);
      $search_path->setOptions([]);
      $current_request = \Drupal::request();
      $uri = $current_request->getPathInfo();
      $keywords = $current_request->query->get($this->configuration['search_identifier']);
      $title = !empty($keywords) ? $this->configuration['search_title'] . ' ' . $keywords : $this->t('All results');
      if ($search_path->toString() == $uri) {
        $build['pmmi_search_result_title_block']['#markup'] = '<h2 class="headline2">' . $title . '</h2>';
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
