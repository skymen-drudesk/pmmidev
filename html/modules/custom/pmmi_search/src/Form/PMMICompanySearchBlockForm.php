<?php

namespace Drupal\pmmi_search\Form;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the PMMI Company Search Form.
 */
class PMMICompanySearchBlockForm extends FormBase {

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PMMICompanySearchBlockForm object.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
   *   The subdivision repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface;
   *   The entity type manager service.
   */
  public function __construct(CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_company_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = NULL) {
    $wrapper_id = 'sales-agent-directory-address';

    // Describe address filter.
    $form['address'] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $form['address']['country_code'] = [
      '#type' => 'selectize',
      '#title' => $this->t('Country'),
      '#options' => $this->countryRepository->getList(),
      '#multiple' => TRUE,
      '#settings' => [
        'placeholder' => $this->t('Select Country'),
        'plugins' => ['remove_button', 'prevent_items_backspace_delete'],
      ],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_class($this), 'addressAjaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['address']['administrative_area'] = [
      '#type' => 'selectize',
      '#title' => $this->t('State/Region'),
      '#multiple' => TRUE,
      '#settings' => [
        'placeholder' => $this->t('Select State/Region'),
        'plugins' => ['remove_button', 'prevent_items_backspace_delete'],
      ],
      '#input_group' => TRUE,
      '#access' => FALSE,
    ];

    $countries = [];

    // Override values after country has been changed!
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#name'] == 'country_code') {
      $countries = $triggering_element['#value'];
    }

    // Show or hide second level of hierarchy in accordance with selected
    // country.
    if ($countries) {
      $areas = [];
      foreach ($countries as $value) {
        $subdivisions = $this->subdivisionRepository->getList([$value]);
        $areas = $areas + $subdivisions;
      }

      // Show subdivision field.
      if ($areas) {
        $form['address']['administrative_area']['#options'] = $areas;
        $form['address']['administrative_area']['#access'] = TRUE;
      }
    }
    else {
      $form['address']['administrative_area']['#options'] = [];
    }

    // Describe "Industries Served" filter.
    $form['industries'] = [
      '#type' => 'details',
      '#title' => $this->t('Industries Served'),
    ];
    $form['industries']['field_industries_served'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getIndustriesOptions(),
      '#limit_validation_errors' => [],
    ];

    // Describe "Types of equipment sold" filter.
    $form['equipments'] = [
      '#type' => 'details',
      '#title' => $this->t('Types of equipment sold'),
    ];
    $form['equipments']['field_equipment_sold_type'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getEquipmentsOptions(),
    ];

    // Describe "Attending PMMI show" filter.
    $form['shows'] = [
      '#type' => 'details',
      '#title' => $this->t('Attending PMMI show'),
    ];
    $form['shows']['pmmi_shows'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getTradeShowsOptions(),
    ];

    // Describe "Keyword" filter.
    $form['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#placeholder' => $this->t('Enter keyword'),
    ];

    $form['country_list'] = [
      '#type' => 'value',
      '#value' => $this->countryRepository->getList(),
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

    $filters = [
      'country_code',
      'administrative_area',
      'field_industries_served',
      'field_equipment_sold_type',
      'pmmi_shows'
    ];

    foreach ($filters as $filter) {
      if (!empty($values[$filter])) {
        $items = array_filter($values[$filter]);
        foreach (array_values($items) as $key => $item) {
          $query["{$filter}[$key]"] = $item;
        }
      }
    }

    // Fulltext filter.
    if (!empty($values['keywords'])) {
      $query['keywords'] =  str_replace(' ', '+', $values['keywords']);
    }

    $url = Url::fromUri('internal:/sales-agent-directory/search/results');
    $url->setOption('query', $query);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Get industries served options.
   */
  protected function getIndustriesOptions() {
    $options = array();

    $terms = $this->entity_type_manager->getStorage('taxonomy_term')->loadTree('industries_served');
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return $options;
  }

  /**
   * Get types of equipment sold options.
   */
  protected function getEquipmentsOptions() {
    $options = array();

    $terms = $this->entity_type_manager->getStorage('taxonomy_term')->loadTree('equipment_sold_type');
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return $options;
  }

  /**
   * Get trade shows options.
   */
  protected function getTradeShowsOptions() {
    $trade_shows = array();

    foreach (pmmi_company_contact_get_active_trade_shows() as $show) {
      $trade_shows[$show->id()] = $show->getName();
    }

    return $trade_shows;
  }
}
