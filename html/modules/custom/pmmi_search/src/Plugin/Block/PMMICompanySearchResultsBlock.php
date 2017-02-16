<?php

namespace Drupal\pmmi_search\Plugin\Block;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PMMICompanySearchResultsBlock' block.
 *
 * @Block(
 *  id = "pmmi_company_search_results_block",
 *  admin_label = @Translation("PMMI Company Search Results block"),
 *  category = @Translation("PMMI Search")
 * )
 */
class PMMICompanySearchResultsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a PMMICompanySearchResultsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   *   The subdivision repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    if (!$view = Views::getView('search_sales_agent_directory')) {
      // Search view is not exist.
      return $output;
    }

    $view->setDisplay('block_1');
    $view->build();
    $this->viewAddFilters($view);
    $view->preExecute();
    $view->execute();

    if ($results = $view->query->getSearchApiResults()) {
      // Add filters to the search API query.
      $result_count = $results->getResultCount();

      $output['header'] = $this->buildHeader($result_count);
      $output['view'] = $view->render();
    }

    return $output;
  }

  /**
   * Build header which includes information about added filter.
   */
  protected function buildHeader($result_count) {
    $filters = [];

    // Get title.
    $title = $this->stringTranslation->formatPlural($result_count, 'Search Results <span>(@count company)</span>', 'Search Results <span>(@count companies)</span>');

    // Get all available filters.
    if ($query_params = \Drupal::request()->query->all()) {
      foreach ($query_params as $param => $values) {
        $values = is_array($values) ? $values : [$values];
        if ($filter = $this->getFilterInfo($param, $values)) {
          $filters[] = $filter;
        }
      }
    }

    return [
      '#theme' => 'pmmi_company_search_results',
      '#title' => $title,
      '#filters' => $filters,
    ];
  }

  /**
   * Builds block with information about some filter.
   *
   * @param $filter string
   *   The filter machine name.
   * @param $values array array
   *   The filter values.
   *
   * @return array
   *   The renderable array with information about the filter.
   */
  protected function getFilterInfo($filter, array $values) {
    $items = [];
    $query_params = \Drupal::request()->query->all();

    switch ($filter) {
      // Country/state filter info.
      case 'country_code':
        $countries = $this->countryRepository->getList();

        foreach ($values as $value) {
          if (isset($countries[$value])) {
            // Bypass all administrative_area values and add information about
            // selected states if they are related to some selected country.
            $areas_names = [];
            if (!empty($query_params['administrative_area'])) {
              $subdivisions = $this->subdivisionRepository->getList([$value]);
              foreach ($query_params['administrative_area'] as $area) {
                if (array_key_exists($area, $subdivisions)) {
                  $areas_names[] = $subdivisions[$area];
                }
              }
            }

            $items[$value] = !$areas_names ? $countries[$value] : $countries[$value] . ': ' . implode(', ', $areas_names);
          }
        }
        break;

      case 'field_industries_served':
      case 'field_equipment_sold_type':
      case 'pmmi_shows':
        foreach ($values as $value) {
          $term = $this->entity_type_manager->getStorage('taxonomy_term')->load($value);
          if ($term) {
            $items[] = $term->getName();
          }
        }
        break;

      case 'keywords':
        if ($values && ($value = reset($values))) {
          $items[] = str_replace('+', ' ', $value);
        }
        break;

    }

    return !$items ? [] : ['#theme' => 'item_list', '#title' => $this->filterGetName($filter), '#items' => $items];
  }

  /**
   * Get filter name by its key.
   *
   * @param $key string
   *   The filter key.
   *
   * @return string
   *   The filter name.
   */
  protected function filterGetName($key) {
    switch ($key) {
      case 'country_code':
        return $this->t('Countries');

      case 'field_industries_served':
        return $this->t('Industries served');

      case 'field_equipment_sold_type':
        return $this->t('Equipment type');

      case 'pmmi_shows':
        return $this->t('PMMI Show');

      case 'keywords':
        return $this->t('Keyword');
    }
  }

  /**
   * Add additional filters to SearchApi query before the execution.
   */
  protected function viewAddFilters(&$view) {
    if (!$query_params = \Drupal::request()->query->all()) {
      // There are no available filters.
      return;
    }

    $where = $view->query->getWhere();
    $group_id = max(array_keys($where));

    // Bypass all filters and apply them.
    foreach ($query_params as $param => $values) {
      // Use IN operator.
      if ($values && is_array($values)) {
        $view->query->setWhereGroup('OR', ++$group_id);
        foreach ($values as $value) {
          $view->query->addCondition($param, $value, 'IN', $group_id);
        }
      }
    }

    $view->query->build($view);
  }
}
