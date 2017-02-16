<?php
/**
 * @file
 * The main file that describes the "overrides" of the original views.
 */

/**
 * Implements hook_views_data().
 */
function odata_views_data() {

  $data = array();
  /** @var \Drupal\Core\Entity\EntityTypeManager $etm */
  $etm = \Drupal::service('entity_type.manager');
  $odata_enities = $etm->getStorage('odata_entity')->loadMultiple();
//  $query = db_select('odata_endpoints', 'oe')
//    ->fields('oe', array('title', 'url', 'options', 'endpoint_name'))
//    ->execute();
  /** @var \Drupal\odata\Entity\OdataEntity $entity */
  foreach ($odata_enities as $entity) {
    $data[$entity->id()]['table']['group'] = t('Open Data');

    $data[$entity->id()]['table']['base'] = array(
      // This is the identifier field for the view.
      'field' => $entity->getEndpointUrl() . '/' . $entity->getCollection(),
      'title' => $entity->label(),
      'help' => t('Directly query oData:@title to get some data realtime.', array('@title' => $entity->label())),
      'query_id' => 'odata_view_query',
//      'query class' => 'odata_query',
      'accept_request_header' => $entity->getRequestFormat(),
    );

    $data[$entity->id()]['table']['endpoint'] = $entity->getEndpointUrl() . '/' . $entity->getCollection();
  }

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function odata_views_data_alter(&$data) {

  /** @var \Drupal\Core\Entity\EntityTypeManager $etm */
  $etm = \Drupal::service('entity_type.manager');
  $odata_enities = $etm->getStorage('odata_entity')->loadMultiple();

  /** @var \Drupal\odata\Entity\OdataEntity $entity */
  foreach ($odata_enities as $entity) {
    $schema = unserialize($entity->getCollectionSchema());
    $collection = $entity->getCollection();

    $schema = _odata_return_fields($collection, $schema);
    $data[$entity->id()] = array_merge($data[$entity->id()], $schema);

    // Override the combine filter.
    $data['views']['combine']['filter'] = array(
      'handler' => 'ODataHandlerFilterCombine',
    );
  }
}

/**
 * Implements hook_views_plugins().
 */
function odata_views_plugins() {
  return array(
    'query' => array(
      'odata_query' => array(
        'title' => t('oData query'),
        'help' => t('Query will be generated and executed using current oData endpoint.'),
        'handler' => 'OdataPluginQueryOdata',
      ),
    ),
  );
}

/**
 * Returns schema array to be inserted in the hook_views_data_alter() array.
 *
 * @param string $group
 *   Group name to group fields together.
 *
 * @param array $field
 *   Array with field properties.
 *
 * @return array
 *   Returns an array to be inserted in hook_views_data_alter().
 */
function _odata_return_fields($group, $field) {

  foreach ($field as $name => $attributes) {

    // Find the associated type.
    $type = odata_convert_type($attributes['Type']);

    $schema[$name] = array(
      'title' => $name,
      'group' => $group,
      'field' => array(
        'handler' => 'ODataHandlerField' . $type,
        'click sortable' => TRUE,
        'help' => $name,
      ),
      'sort' => array(
        'help' => t('Order results as @name.', array('@name' => $attributes['Name'])),
        'handler' => 'ODataHandlerSort' . $type,
      ),
      'filter' => array(
        'help' => t('Filter results by @name.', array('@name' => $attributes['Name'])),
        'handler' => 'ODataHandlerFilter' . $type,
      ),
      'argument' => array(
        'help' => t('Filter results by @name.', array('@name' => $attributes['Name'])),
        'handler' => 'ODataHandlerArgument' . $type,
      ),
    );

    // If it is a complex type, we need a recursion to handle its fields.
    if ($attributes['Type'] == 'ComplexType') {
      $schema[$name]['field']['complex_fields'] = $attributes['Complex'];
    }
  }

  return $schema;
}
