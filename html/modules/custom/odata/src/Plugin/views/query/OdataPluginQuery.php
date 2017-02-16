<?php

namespace Drupal\odata\Plugin\views\query;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OdataPluginQueryOdata
 */

/**
 * Defines a Views query class for Odata Entities.
 *
 * Reference: http://www.sitepoint.com/drupal-8-version-entityfieldquery/.
 *
 * @ViewsQuery(
 *   id = "odata_views_query",
 *   title = @Translation("Odata Entity"),
 *   help = @Translation("Odata Entity Query")
 * )
 */
class OdataPluginQuery extends QueryPluginBase {

  use UncacheableDependencyTrait;

  /**
   * Number of results to display.
   *
   * @var int
   */
  protected $limit;

  /**
   * Offset of first displayed result.
   *
   * @var int
   */
  protected $offset;

  /**
   * The query that will be executed.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * Array of all encountered errors.
   *
   * Each of these is fatal, meaning that a non-empty $errors property will
   * result in an empty result being returned.
   *
   * @var array
   */
  protected $errors = array();

  /**
   * Whether to abort or executing it.
   *
   * @var bool
   */
  protected $abort = FALSE;

  /**
   * The query's conditions representing the different Views filter groups.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * The logger to use for log messages.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * Stores the Helper object which handles the many_to_one complexity.
   *
   * @var \Drupal\views\ManyToOneHelper
   */
  protected $helper = NULL;

  /**
   * Config entity Id.
   *
   * @var string
   */
  protected $configEntityId;


  // A simple array of order by clauses.
  public $orderby = array();

  // A simple array of group by clauses.
  public $groupby = array();

  /**
   * A pager plugin that should be provided by the display.
   *
   * @var views_plugin_pager
   */
  public $pager = NULL;

  // An array of fields.
  public $fields = array();

  // An array mapping table aliases and field names to field aliases.
  public $field_aliases = array();

  // The default operator to use when connecting the WHERE groups. May be
  // AND or OR.
  public $group_operator = 'AND';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $OdataManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->OdataManager = \Drupal::service('odata.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id,
      $plugin_definition);

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.factory')->get($plugin_id);
    $plugin->setLogger($logger);

    return $plugin;
  }

  /**
   * Retrieves the logger to use for log messages.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Sets the logger to use for log messages.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The new logger.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * Builds the necessary info to execute the query.
   *
   * @param ViewExecutable $view
   *   The view which is executed.
   */
  public function build(ViewExecutable $view) {

    // Store the view in the object to be able to use it later.
    $this->view = $view;

    if ($this->shouldAbort()) {
      return;
    }

    // Initialize the pager and let it modify the query to add limits.
    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ViewExecutable $view) {
    \Drupal::moduleHandler()->invokeAll('addWhere', array($view, $this));
  }

  /**
   * Generates a query and a countquery.
   *
   * @param bool $get_count
   *   (Optional) Provide a countquery if this is true, otherwise provide a
   *   normal query. Defaults to FALSE.
   */
  public function query($get_count = FALSE) {

    $query = array();

    // Add fields the user has selected in the Views UI.
    if (isset($this->fields)) {
      foreach ($this->fields as $key => $field) {
        $fields[] = $field['alias'];
      }
      // Create a comma separated string.
      $query[] = '$select=' . implode(',', $fields);
    }

    // Add filters.
    if (!empty($this->where)) {
      foreach ($this->where as $group_id => $group_filters) {

        // If no conditions, continue.
        if (!$group_filters['conditions']) {
          continue;
        }

        $filters = array();

        foreach ($group_filters['conditions'] as $id => $filter) {
          $filter['operator'] = ($filter['operator'] == 'formula') ? '' : $filter['operator'];
          $filters[] = $filter['field'] . $filter['operator'] . $filter['value'];
        }

        $query_filters[] = "(" . implode("+" . strtolower($this->where[$group_id]['type']) . "+", $filters) . ")";
      }

      $query[] = '$filter=' . implode("+" . strtolower($this->group_operator) . "+", $query_filters);
    }

    // Add Sorting Criteria if any.
    if (!empty($this->orderby)) {
      foreach ($this->orderby as $id => $field_to_order) {
        $orderby[] = $field_to_order['field'] . '+' . strtolower($field_to_order['direction']);
      }
      // Populate $orderby.
      $query[] = '$orderby=' . implode(',', $orderby);
    }

    // @todo add $skip & $top in the query,
    // so it can displayed in the views preview
    return implode('&', $query);
  }

  /**
   * Executes the query and fills the associated view object.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->pager['current_page'].
   *
   * $view->result should contain an array of objects. The array must use a
   * numeric index starting at 0.
   *
   * @param view $view
   *   The view which is executed.
   */
  public function execute(ViewExecutable $view) {
//    $reply = drupal_http_request($request, array(
//      'headers' => array(
//        'Accept' => $format,
//        'Authorization' => 'Basic ' . base64_encode('SUMMIT:pmg@123'),
//      ),
//    ));
    $start = microtime(TRUE);

    $query = $view->build_info['query'];
    $count_query = $view->build_info['count_query'];
    $base_field = $view->storage->get('base_field');
    $request_data = [
      'url' => $base_field,
      'username' => 'SUMMIT',
      'password' => 'pmg@123',
    ];
    // If we are using the pager, calculate the total number of results.
    if ($view->pager->usePager()) {
      try {
        // Execute count query for pager.
        $request_data['url'] = $base_field . '/$count?' . $count_query;
        $count = $this->OdataManager->getODataDataRequest($request_data, 'json');
        $view->total_rows = (is_array($count)) ? count($count) : $count;
        $this->pager->total_items = $view->total_rows;

        if (!empty($this->pager->options['offset'])) {
          $this->pager->total_items -= $this->pager->options['offset'];
        }

        $view->pager->updatePageInfo();
      } catch (\Exception $e) {
        if (!empty($view->simpletest)) {
          throw($e);
        }
        // Show the full exception message in Views admin.
        if (!empty($view->live_preview)) {
          drupal_set_message($e->getMessage(), 'error');
        }
        else {
          vpr('Exception in @human_name[@view_name]: @message', array(
            '@human_name' => $view->human_name,
            '@view_name' => $view->name,
            '@message' => $e->getMessage(),
          ));
        }
        return;
      }
    }

    if (!empty($this->limit)) {
      $top = (isset($this->limit)) ? $this->limit : 0;
      $query .= '&$top=' . $top;
    }

    if (!empty($this->offset)) {
      $skip = (isset($this->offset)) ? $this->offset : 0;
      $query .= '&$skip=' . $skip;
    }

//    $request_data['url'] = $base_field . '/?' . $query;
    $query = '$select=*&$filter=CommitteeMasterCustomer eq \'C0000010\'';
    $request_data['url'] = $base_field . '/?' . $query;
    // Get results.
    $result = $this->OdataManager->getODataDataRequest($request_data, 'json');

    // Abort if it's empty.
    if (is_null($result)) {
      return;
    }

    $view->result = array();

    foreach ($result as $id => $value) {
      // Remove the metadata entry.
      unset($result[$id]['__metadata']);
      $result[$id] = (object) $value;
    }

    $view->result = $result;

    $view->execute_time = microtime(TRUE) - $start;
  }

  /**
   * Set a LIMIT on the query, specifying a maximum number of results.
   */
  public function setLimit($limit) {
    $this->limit = ($limit) ? $limit : 10000000;
  }

  /**
   * Add a field to the query.
   *
   * Mostly a fork of the parent_views::addField() method. There are
   * only parts that make sense.
   *
   * @param sting $table
   *   The table this field is attached to.
   * @param string $field
   *   The name of the field to add. This may be a real field or a formula.
   * @param string $alias
   *   The alias to create.
   * @param array $params
   *   An array of parameters additional to the field.
   *
   * @return string
   *   The name that this field can be referred to as.
   *   Usually this is the alias.
   */
  function addField($table, $field, $alias = '', $params = array()) {

    // Make sure an alias is assigned.
    $alias = $alias ? $alias : $field;

    // Create a field info array.
    $field_info = array(
        'field' => $field,
        'table' => $table,
        'alias' => $alias,
      ) + $params;

    if (empty($this->fields[$alias])) {
      $this->fields[$alias] = $field_info;
    }

    // Keep track of all aliases used.
    $this->field_aliases[$table][$field] = $alias;

    return $alias;
  }

  /**
   * Add a simple filter clause to the query.
   *
   * Mostly a fork of the parent_views::addWhere() method. There are
   * only parts that make sense.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );
  }

  /**
   * Add an ORDER BY clause to the query.
   */
  function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
    // Only ensure the table if it's not the special random key.
    if ($table && $table != 'rand') {
      $this->ensureTable($table);
    }

    // Only fill out this aliasing if there is a table;
    // otherwise we assume it is a formula.
    if (!$alias && $table) {
      $as = $table . '_' . $field;
    }
    else {
      $as = $alias;
    }

    if ($field) {
      $as = $this->addField($table, $field, $as, $params);
    }

    $this->orderby[] = array(
      'field' => $as,
      'direction' => strtoupper($order),
    );
  }

  /**
   * Add a complex WHERE clause to the query.
   *
   * @param string $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param string $snippet
   *   The snippet to check. This can be either a column or
   *   a complex expression like "UPPER(table.field) = 'value'"
   * @param array $args
   *   An associative array of arguments.
   */
  public function addWhereExpression($group, $snippet, $args = array()) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => 'formula',
    );
  }

  /**
   * Aborts the query.
   *
   * Used by handlers to flag a fatal error which shouldn't be displayed but
   * still lead to the view returning empty and the query not being executed.
   *
   * @param string|null $msg
   *   Optionally, a translated, unescaped error message to display.
   */
  public function abort($msg = NULL) {
    if ($msg) {
      $this->errors[] = $msg;
    }
    $this->abort = TRUE;
  }

  /**
   * Checks whether this query should be aborted.
   *
   * @return bool
   *   TRUE if the query should/will be aborted, FALSE otherwise.
   */
  public function shouldAbort() {
    return $this->abort;
  }

}
