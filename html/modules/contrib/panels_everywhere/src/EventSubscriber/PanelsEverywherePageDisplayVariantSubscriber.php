<?php

namespace Drupal\panels_everywhere\EventSubscriber;

use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\Entity\PageVariantAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the appropriate page display variant from 'site_template'.
 */
class PanelsEverywherePageDisplayVariantSubscriber implements EventSubscriberInterface {

  use ConditionAccessResolverTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new PageManagerRoutes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityStorage = $entity_type_manager->getStorage('page');
  }

  /**
   * Selects the page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $page = $this->entityStorage->load('site_template');
    $route_options = $event->getRouteMatch()->getRouteObject()->getOptions();

    $isAdminRoute = array_key_exists('_admin_route', $route_options) && $route_options['_admin_route'];

    if (!is_object($page) || !$page->get('status') || $isAdminRoute) {
      return;
    }

    foreach ($page->getVariants() as $variant) {
      $access = $this->resolveConditions($variant->getSelectionConditions(), $variant->getSelectionLogic());

      if (!$access) {
        continue;
      }

      $plugin = $variant->getVariantPlugin();
      $event->setPluginId($plugin->getPluginId());
      $event->setPluginConfiguration($plugin->getConfiguration());
      $event->setContexts($variant->getContexts());
      break;
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}
