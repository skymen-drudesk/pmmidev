<?php

namespace Drupal\pmmi_search\Form;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the PMMI Admin Panel Agent Search Form.
 */
class PMMIAdminPanelAgentSearchBlockForm extends FormBase {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Country\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a PMMIAdminPanelAgentSearchBlockForm object.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
   *   The subdivision repository.
   */
  public function __construct(CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_admin_panel_agent_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    $qp = \Drupal::request()->query->all();
    $countries = $this->countryRepository->getList();

    // We can use static wrapper here.
    $wrapper_id = 'admin-panel-sales-agent-directory-address';

    // Add link to quick add new company node.
    $url = Url::fromUri('internal:/node/add/company');
    $form['create_company']['#markup'] = Link::fromTextAndUrl($this->t('Add new company'), $url)->toString();

    // Describe address filters.
    $form['address'] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $form['address']['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['_none' => $this->t('- Any -')] + $countries,
      '#default_value' => isset($qp['country_code']) ? $qp['country_code'] : '_none',
      '#ajax' => [
        'callback' => [get_class($this), 'addressAjaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['address']['administrative_area'] = [
      '#type' => 'select',
      '#title' => $this->t('State/Region'),
      '#access' => FALSE,
    ];

    $selected_country = NULL;

    // Override values after country has been changed!
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#name'] == 'country_code') {
      $selected_country = $triggering_element['#value'];
    }
    elseif (isset($qp['country_code']) && !empty($countries[$qp['country_code']])) {
      $selected_country = $qp['country_code'];
    }

    // Show or hide second level of hierarchy in accordance with selected
    // country.
    if ($selected_country && $selected_country != '_none') {
      $subdivisions = $this->subdivisionRepository->getList([$selected_country]);

      // Show subdivision field.
      if ($subdivisions) {
        $form['address']['administrative_area']['#options'] = ['_none' => $this->t('- Any -')] + $subdivisions;
        $form['address']['administrative_area']['#access'] = TRUE;
        $form['address']['administrative_area']['#default_value'] = isset($qp['administrative_area']) ? $qp['administrative_area'] : '_none';
      }
    }
    else {
      $form['address']['administrative_area']['#options'] = [];
    }

    // Allow fulltext filtering.
    $form['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword search'),
      '#default_value' => isset($qp['keywords']) ? str_replace('+', ' ', $qp['keywords']) : '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * Address ajax callback.
   */
  public static function addressAjaxRefresh(array $form, FormStateInterface $form_state) {
    return $form['address'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];
    $values = $form_state->getValues();

    // Filtering by country/state.
    foreach (['country_code', 'administrative_area'] as $filter) {
      if ($values[$filter] && $values[$filter] != '_none') {
        $query[$filter] = $values[$filter];
      }
    }

    // Fulltext filtering.
    if (!empty($values['keywords'])) {
      $query['keywords'] = str_replace(' ', '+', $values['keywords']);
    }

    $url = Url::fromUri('internal:/admin/sales-agent-directory');
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }
}
