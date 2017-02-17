<?php

namespace Drupal\pmmi_address\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pmmi_address\Plugin\Field\FieldType\CountryAreaItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'country_area_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "country_area_plain",
 *   label = @Translation("Country & Administrative area Plain"),
 *   field_types = {
 *     "country_area",
 *   },
 * )
 */
class CountryAreaPlainFormatter extends  FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs an AddressPlainFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $divisions = $country = [];

    foreach ($items as $item) {
      $division = $item->value;
      $division = explode('::', $division);
      $country = $this->countryRepository->getList();
      if (empty($division[1])) {
        $elements[]['#markup'] = '<strong>' . $country[$division[0]] . '</strong>';
      } else {
        $divisions[$division[0]][] = $division[1];
      }
    }

    foreach ($divisions as $div => $subdiv) {
      $country_name = $country[$div];
      $subdivisionsList = $this->subdivisionRepository->getList(array($div));
      $title = [];
      foreach ($subdiv as $key) {
        $title[] = $subdivisionsList[$key];
      }
      $elements[]['#markup'] = '<strong>' . $country_name . '</strong>' . ': ' . implode(', ', $title);
      unset($title);
    }

    return $elements;
  }
}
