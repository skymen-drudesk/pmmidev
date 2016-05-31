<?php
/**
 * @file
 * Contains \Drupal\audience_select\EventSubscriber\AudienceSelectSubscriber
 */

// Declare the namespace that our event subscriber is in. This should follow the
// PSR-4 standard, and use the EventSubscriber sub-namespace.
namespace Drupal\audience_select\EventSubscriber;

// This is the interface we are going to implement.
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// This class contains the event we want to subscribe to.
use Symfony\Component\HttpKernel\KernelEvents;
// Our event listener method will receive one of these.
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
// We'll use this to perform a redirect if necessary.
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Url;

/**
 * Subscribe to KernelEvents::REQUEST events and redirect to /gateway if
 * audience_select_audience cookie is not set.
 */
class AudienceSelectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    //ddl($events, 'events');
    return $events;
  }

  /**
   * Checks for audience_select_audience cookie and redirects to /gateway if not
   * set when the KernelEvents::REQUEST event is dispatched. If audience query
   * parameter exists, sets audience_select_audience cookie.
   */

  public function checkForRedirection() {
    $request = \Drupal::request();
    $request_uri = $request->getRequestUri();
    drupal_set_message($request_uri);

    // Get audience if query parameter exists.
    if ($request->query->has('audience')) {
      $audience = $request->query->get('audience');
    }

    // If audience_select_audience cookie is not set, redirect to gateway page.
    if (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != '/gateway'
      && !$request->cookies->has('audience_select_audience')
    ) {
      drupal_set_message('no cookie');
      $path = Url::fromRoute('gateway')->toString();
      drupal_set_message($path);
      $response = new LocalRedirectResponse($path);
      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
        'cookies:' . 'audience_select_audience',
        'session.exists'
      ]));
      return $response;
    }
    // If audience_select_audience cookie is not set and route is / with
    // audience query parameter, set cookie.
    elseif (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != '/gateway'
      && !$request->cookies->has('audience_select_audience')
      && isset($audience)
    ) {
      $request->cookies->set('audience_select_audience', $audience);
    }
    return $request;
  }
}
