<?php
/**
 * @file
 * Contains \Drupal\audience_select\EventSubscriber\AudienceSelectSubscriber
 */

namespace Drupal\audience_select\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Subscribe to KernelEvents::REQUEST events and redirect to /gateway if
 * audience_select_audience cookie is not set.
 */
class AudienceSelectSubscriber implements EventSubscriberInterface {

  /**
   * Checks for audience_select_audience cookie and redirects to /gateway if not
   * set when the KernelEvents::REQUEST event is dispatched. If audience query
   * parameter exists, sets audience_select_audience cookie.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();

    $request_uri = $request->getRequestUri();

    // Get audience if query parameter exists.
    if ($request->query->has('audience')) {
      $audience = $request->query->get('audience');
    }

    // If audience_select_audience cookie is not set, redirect to gateway page.
    if (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != '/gateway'
      && !$request->cookies->has('audience_select_audience')
      && !isset($audience)
    ) {
      $path = Url::fromRoute('gateway')->toString();

      $response = new TrustedRedirectResponse($path);
      $event->setResponse($response);
    }

    // If route is / with audience query parameter, set cookie.
    elseif (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != '/gateway'
      && isset($audience)
    ) {
      $response = new TrustedRedirectResponse('/');
      // Set cookie without httpOnly, so that JavaScript can delete it.
      setcookie('audience_select_audience', $audience, time() + (86400 * 365), '/', NULL, FALSE, FALSE);
      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
        'cookies:' . 'audience_select_audience',
      ]));
      $event->setResponse($response);
    }

    // If audience_select_audience cookie is set and route is /gateway redirect
    // to frontpage.
    elseif (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri == '/gateway'
      && $request->cookies->has('audience_select_audience')
    ) {
      $response = new TrustedRedirectResponse('/');
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = array('checkForRedirection', 33);
    return $events;
  }

}
