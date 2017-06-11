<?php

namespace Drupal\pmmi_page_manager_search\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;
use stdClass;


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
 *   entity_keys = {
 *     "id" = "pid",
 *   },
 * )
 */
class PageManagerSearch extends ContentEntityBase implements ContentEntityInterface {

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
  public static function load($pid, $encode = FALSE) {
    if ($encode === TRUE) {
      $pid = pmmi_page_manager_search_decoder($pid);
    }
    $page_variant = \Drupal::entityTypeManager()
      ->getStorage('page_variant')
      ->load($pid);
    $entity = new stdClass();

    if ($page_variant instanceof PageVariant) {
      $page = $page_variant->getPage();

      if ($page instanceof Page) {
        if ($encode === TRUE) {
          $entity->pid = $pid;
        }
        else {
          $entity->pid = pmmi_page_manager_search_encoder($pid);
        }
        $entity->title = $page_variant->label();
        $render = \Drupal::entityTypeManager()
          ->getViewBuilder('page_variant')
          ->view($page_variant);

        $content = \Drupal::service('renderer')->renderRoot($render);
        $content = strip_tags($content);
        $content = trim(preg_replace('/\s+/', ' ', $content));

        $entity->content = $content;
        $entity->path = $page->getPath();
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(array $ids = NULL) {
    $results = [];

    foreach ($ids as $id) {
      $results[] = PageManagerSearch::load($id);
    }

    return $results;
  }
}
