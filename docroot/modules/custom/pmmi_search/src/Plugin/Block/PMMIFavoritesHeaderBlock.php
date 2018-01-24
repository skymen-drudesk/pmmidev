<?php

namespace Drupal\pmmi_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'PMMIFavoritesHeaderBlock' block.
 *
 * @Block(
 *  id = "pmmi_favorites_header_block",
 *  admin_label = @Translation("PMMI Favorites Header block"),
 *  category = @Translation("PMMI Search")
 * )
 */
class PMMIFavoritesHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => $this->t('My Favorites'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    if (!$view = Views::getView('my_favorites_companies')) {
      // Search view is not exist.
      return $output;
    }
    $view->setDisplay('block_1');
    $view->build();
    $view->preExecute();
    $view->execute();
    $output['#prefix'] = '<div class="favorites-header">';
    $output['#suffix'] = '</div>';
    $output['title']['#markup'] =
    $output['title']['#markup'] = '<h2 class="title">' . $this->configuration['title'];
    $output['title']['#markup'] .= '<span> ' . $this->formatPlural(
      $view->total_rows,
      '(1 company)',
      '(@count companies)'
     ) . '</span></h2>';
    if (isset($view->header['downloads_favorites_button'])) {
      $output['download_link'] = $view->header['downloads_favorites_button']->render();
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $this->configuration['title'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
  }

}
