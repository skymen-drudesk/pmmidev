<?php

namespace Drupal\pmmi_import\Plugin\migrate\process;

use Drupal\address\Repository\CountryRepository;
use Drupal\address\Repository\SubdivisionRepository;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is PMMI Address plugin to handle specific address data.
 *
 * @MigrateProcessPlugin(
 *   id = "pmmiaddressfield"
 * )
 */
class PMMIAddressField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \Drupal\address\Repository\CountryRepository;
   */
  protected $country_repository;

  /**
   * The subdivision repository.
   *
   * @var \Drupal\address\Repository\SubdivisionRepository;
   */
  protected $subdivision_repository;

  /**
   * Constructs a MachineName plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\address\Repository\CountryRepository $country_repository
   *   The country repository.
   * @param \Drupal\address\Repository\SubdivisionRepository $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountryRepository $country_repository, SubdivisionRepository $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->country_repository = $country_repository;
    $this->subdivision_repository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $address_field = substr($destination_property, strpos($destination_property, '/') + 1);

    switch ($address_field) {
      case 'country_code':
        if ($country_code = $this->getCountryCodeByName($value)) {
          return $country_code;
        }
        else {
          throw new MigrateException($this->t('Country "@country" isn\'t correct.', array(
            '@country' => $value,
          )));
        }
        break;

      case 'administrative_area':
        $source = $row->getSource();
        $country = $source[$this->configuration['parent']];

        // Just to be sure that all was imported correctly.
        if ($value && $country_code = $this->getCountryCodeByName($country)) {
          $areas = $this->subdivision_repository->getList(array($country_code));

          if (isset($areas[$value])) {
            return $value;
          }
          else {
            throw new MigrateException($this->t('Administrative area "@area" isn\'t correct for "@country".', array(
              '@area' => $value,
              '@country' => $country,
            )));
          }
        }
        break;

      default:
        return $value;
    }
  }

  /**
   * Get country code by its name.
   */
  protected function getCountryCodeByName($country) {
    $countries = $this->country_repository->getList();
    return array_search(strtolower($country), array_map('strtolower', $countries));
  }
}
