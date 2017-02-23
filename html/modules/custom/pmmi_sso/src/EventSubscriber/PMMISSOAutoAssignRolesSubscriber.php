<?php

namespace Drupal\pmmi_sso\EventSubscriber;

use Drupal\pmmi_sso\Event\PMMISSOPreRegisterEvent;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PMMISSOAutoAssignRoleSubscriber.
 */
class PMMISSOAutoAssignRolesSubscriber implements EventSubscriberInterface {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * PMMISSOAutoAssignRoleSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory instance.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->settings = $config->get('pmmi_sso.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PMMISSOHelper::EVENT_PRE_REGISTER][] = ['assignRolesOnRegistration'];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Assign roles to a user that just registered via PMMI SSO.
   *
   * @param PMMISSOPreRegisterEvent $event
   *   The event object.
   */
  public function assignRolesOnRegistration(PMMISSOPreRegisterEvent $event) {
    $auto_assigned_roles = $this->settings->get('user_accounts.auto_assigned_roles');
    if (!empty($auto_assigned_roles)) {
      $event->setPropertyValue('roles', $auto_assigned_roles);
    }
  }

}
