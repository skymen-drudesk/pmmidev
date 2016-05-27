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
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  /*public function checkForRedirection(GetResponseEvent $event) {
  //public function checkForRedirection() {
    // If system maintenance mode is enabled, redirect to a different domain.
    $enabled = \Drupal::state()->get('system.maintenance_mode');
    ddl($enabled, 'maintenance mode?');
    if (!isset($enabled) || $enabled === 0) {
      $event->setResponse(new RedirectResponse('http://google.com'));
    }
    else {
      $cookie = new SetCookie();
      // If audience_select_audience cookie is not set, redirect to gateway page.
      $cookie_value = $cookie->getValue();
      ddl($_COOKIE, 'straight cookie');
      ddl($cookie_value, 'cookie using guzzle http');
    }
  }*/

  public function checkForRedirection(GetResponseEvent $event) {
    drupal_set_message('Whoot, I got called!');
    //ddl($event, 'event');
    $request = \Drupal::request();
    $request_uri = $request->getRequestUri();
    //dpm($request, 'request');
    dpm($request->cookies, 'request cookies');
    //dpm($_COOKIE, 'straight up cookie');
    dpm($request_uri);
    //$cookie = new Cookie('audience_select_audience', 'member');
    //dpm($cookie, 'new cookie');
    // If audience_select_audience cookie is not set, redirect to gateway page.
    if (preg_match('/^\/\badmin/i', $request_uri) !== 1 && preg_match('/^\/\buser/i', $request_uri) !== 1 && $request_uri != '/gateway' && !$request->cookies->has('audience_select_audience')) {
      drupal_set_message('NOOOO! WE HAZ NO AUDIENCE_SELECT_AUDIENCE flavored cookie');
      //$url = new Url('gateway')​;
      //$path = $url->toString();​
      $path = Url::fromRoute('gateway')->toString();
      drupal_set_message($path);
      $response = new LocalRedirectResponse($path);
      //$response = new RedirectResponse($path);
      //$response->headers->setCookie(new Cookie('audience_select_audience', 'member', 0, '/', NULL, FALSE, FALSE));
      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . 'audience_select_audience', 'session.exists']));
      dpm($response, 'response');
      $get_cookies = $response->headers->getCookies();
      dpm($get_cookies, 'get cookies');
      return $response;
    }
    else {
      drupal_set_message('YAAAY! WE HAZ AUDIENCE_SELECT_AUDIENCE flavored cookie');
    }
    //$cookie_name = $cookie->getName();
    //$cookie_value = $cookie->getValue();
    //ddl($cookie_name, 'cookie name using symfony http foundation');
    //ddl($cookie_value, 'cookie value using symfony http foundation');
    //$response = new LocalRedirectResponse($request->query->get('destination'));
    // Set cookie without httpOnly, so that JavaScript can delete it.
    //$response->headers->setCookie(new Cookie(BigPipeStrategy::NOJS_COOKIE, TRUE, 0, '/', NULL, FALSE, FALSE));
    //$response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . BigPipeStrategy::NOJS_COOKIE, 'session.exists']));
    //return $response;
  }
}
