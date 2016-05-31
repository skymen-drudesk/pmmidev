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
use Symfony\Component\HttpFoundation\Cookie;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\LocalRedirectResponse;
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

    //$request = \Drupal::request();
    $request_uri = $request->getRequestUri();
    //drupal_set_message($request_uri);


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
      drupal_set_message('no cookie');
      $path = Url::fromRoute('gateway')->toString();
      //drupal_set_message($path);
      //$response = new LocalRedirectResponse($path);
      //$response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . 'audience_select_audience', 'session.exists']));
      //dpm($response, 'response');
      //$get_cookies = $response->headers->getCookies();
      //dpm($get_cookies, 'get cookies');
      //dpm($response);
      //return $response;

      $response = new TrustedRedirectResponse($path);
      //$response->addCacheableDependency($redirect);
      $event->setResponse($response);
    }
    // If audience_select_audience cookie is not set and route is / with
    // audience query parameter, set cookie.
    elseif (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != '/gateway'
      && !$request->cookies->has('audience_select_audience')
      && isset($audience)
    ) {
      drupal_set_message('setting cookie');
      //$response = new LocalRedirectResponse('/');
      $response = new TrustedRedirectResponse('/');
      // Set cookie without httpOnly, so that JavaScript can delete it.
      setcookie('audience_select_audience', $audience, time() + (86400 * 365), '/', NULL, FALSE, FALSE);
      //$response->headers->setCookie(new Cookie('audience_select_audience', $audience, 0, '/', NULL, FALSE, FALSE));
      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
        'cookies:' . 'audience_select_audience',
      ]));
      //drupal_set_message($response);
      return $response;
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
    //ddl($events, 'events');
    return $events;
  }

}
