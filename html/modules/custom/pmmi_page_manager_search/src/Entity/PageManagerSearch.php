<?php

namespace Drupal\pmmi_page_manager_search\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;


/**
 * Defines the Page manager search entity.
 *  Emulate entity for making integration with search_api module.
 *
 * @ingroup pmmi_page_manager_search
 *
 * @ContentEntityType(
 *   id = "page_manager_search",
 *   label = @Translation("Page Manager Search"),
 *   base_table = "page_manager_search",
 *   handlers = {
 *     "storage" = "Drupal\pmmi_page_manager_search\PageManagerSearchStorage",
 *   },
 *   entity_keys = {
 *     "id" = "pid",
 *   },
 * )
 */
class PageManagerSearch extends ContentEntityBase implements ContentEntityInterface {

  /**
   * @var String
   */
  public $title;

  /**
   * @var String
   */
  public $pid;

  /**
   * @var String
   */
  public $content;

  /**
   * @var String
   */
  public $path;

  /**
   * Get Page title.
   *
   * @return String
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get Page ID.
   *
   * @return String
   */
  public function getPid() {
    return $this->pid;
  }

  /**
   * Get Page rendered content.
   *
   * @return String
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Get Page path.
   *
   * @return String
   */
  public function getPath() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['pid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page ID'))
      ->setDescription(t('Machine-readable name'))
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the page.'))
      ->setRequired(TRUE);

    $fields['content'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Content'))
      ->setDescription(t('The content of the page.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function load($sid) {
    $page_variant = pmmi_page_manager_search_get_pages_by_dec($sid);

    $entity = new PageManagerSearch([], 'page_manager_search');
    $contexts = $page_variant->getContexts();
    $context_check = TRUE;

    if (!empty($contexts)) {
      $contexts = array_keys($contexts);
      if (count($contexts) == 1 && in_array('current_user', $contexts)) {
        $context_check = TRUE;
      }
      else {
        $context_check = FALSE;
      }
    }

    if ($page_variant instanceof PageVariant && $context_check === TRUE) {
      $page = $page_variant->getPage();
      if ($page instanceof Page) {
        $entity->pid = $sid;
        $render = \Drupal::entityTypeManager()
          ->getViewBuilder('page_variant')
          ->view($page_variant);
        $entity->title = $page->label();

        $content = \Drupal::service('renderer')->renderRoot($render);
        $content = strip_tags($content);
        $content = trim(preg_replace('/\s+/', ' ', $content));

        $entity->content = $content;
        $entity->path = $page->getPath();
      }
    }
    else {
      $entity = NULL;
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    $results = [];

    if (empty($ids)) {
      $pages = \Drupal::entityTypeManager()
        ->getStorage('page_variant')
        ->loadMultiple();

      foreach ($pages as $page_variant_id => $page_variant) {
        $sid = pmmi_page_manager_search_machine_name_to_dec($page_variant_id);

        if (!empty($sid)) {
          $results[] = PageManagerSearch::load($sid);
          unset($pages[$page_variant_id]);
        }
      }
    }
    else {
      foreach ($ids as $id) {
        $results[] = PageManagerSearch::load($id);
      }
    }

    return $results;
  }
}
