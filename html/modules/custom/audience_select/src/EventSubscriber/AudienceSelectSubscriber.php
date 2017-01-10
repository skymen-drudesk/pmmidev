<?php
/**
 * @file
 * Contains \Drupal\audience_select\EventSubscriber\AudienceSelectSubscriber
 */

namespace Drupal\audience_select\EventSubscriber;

use Drupal\audience_select\Service\AudienceManager;
use Drupal\bootstrap\Plugin\Prerender\Link;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\Routing\Route;

/**
 * Subscribe to KernelEvents::REQUEST events and redirect to /gateway if
 * audience_select_audience cookie is not set.
 */
class AudienceSelectSubscriber implements EventSubscriberInterface {

//  use CacheableResponseTrait;

  /**
   * The audience manager service.
   *
   * @var \Drupal\audience_select\Service\AudienceManager
   */
  protected $AudienceManager;

  /**
   * Constructs a new CurrentUserContext.
   *
   *   The plugin implementation definition.
   * @param \Drupal\audience_select\Service\AudienceManager $audience_manager
   */
  public function __construct(AudienceManager $audience_manager) {
    $this->AudienceManager = $audience_manager;
  }

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
    $exp = new \DateTime();
    $exp->setTimestamp(REQUEST_TIME - 10);

    // Get gateway page URL.
    $gateway_page_url = $this->AudienceManager->getGateway();
    $cacheability = new CacheableMetadata();
//    $cacheability->addCacheContexts(['audience']);
//    $cacheability->setCacheMaxAge(0);

//    $cacheability->addCacheContexts(['cookies:audience_select_audience']);
//    $cache = $request->isNoCache();


    // If audience_select_audience cookie is not set, redirect to gateway page.
    if (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != $gateway_page_url
      && !$request->cookies->has('audience_select_audience')
      && !isset($audience)
    ) {
//      $path = Url::fromRoute($gateway_page_url)->toString();
//      $path = Url::fromUserInput($gateway_page_url);

//      $gateway_page_url = 'http://localhost/gateway';

      $response = new TrustedRedirectResponse($gateway_page_url);
//      $response->addCacheableDependency($cacheability);
//      $response->addCacheableDependency($cacheability->setCacheMaxAge(0));

      $response->setExpires($exp);
//      $cache = $response->isCacheable();
//      $cas = $response->getMaxAge();
//      $response->setMaxAge(0);
      $event->setResponse($response);
    }

    // If route is / with audience query parameter, set cookie.
    elseif (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri != $gateway_page_url
      && isset($audience)
    ) {
      $audience_data = AudienceManager::load($audience);
      $redirect_url = Url::fromUri($audience_data['audience_redirect_url'])
        ->toString();
      $response = new TrustedRedirectResponse($redirect_url);
      // Set cookie without httpOnly, so that JavaScript can delete it.
//      setcookie('audience_select_audience', $audience, time() + (86400 * 365), NULL, NULL, FALSE, FALSE);
      setcookie('audience_select_audience', $audience, time() + (86400 * 365), '/', NULL, FALSE, FALSE);
//      $request->cookies->set('','');
//      $response->addCacheableDependency($cacheability);
//      $response->addCacheableDependency($cacheability->setCacheMaxAge(0));
//      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
//        'audience',
//      ])->addCacheTags(['audience']));
//      $response->setExpires($exp);

//      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
//        'cookies:audience_select_audience'
//      ]));
//      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts([
//        'cookies:' . 'audience_select_audience',
//      ]));
      $event->setResponse($response);
    }

    // If audience_select_audience cookie is set and route is
    // /$gateway_page_url redirect to frontpage.
    elseif (preg_match('/^\/\badmin/i', $request_uri) !== 1
      && preg_match('/^\/\buser/i', $request_uri) !== 1
      && $request_uri == $gateway_page_url
      && $request->cookies->has('audience_select_audience')
    ) {
      $response = new TrustedRedirectResponse('/');
      $response->setExpires($exp);
//      $response->addCacheableDependency($cacheability);
//      $response->addCacheableDependency($cacheability->addCacheContexts(['audience']));
      $event->setResponse($response);
    }
    // If audience_select_audience cookie is set and route is
    // /$gateway_page_url redirect to frontpage.
    elseif ($gateway_page_url == $request_uri) {
//      $response = $event->getResponse();
//      $response->setExpires($exp);
//      $event->setResponse($response);
    }
  }

//  /**
//   * Sets extra headers on successful responses.
//   *
//   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
//   *   The event to process.
//   */
//  public function onRespond(FilterResponseEvent $event) {
//    if (!$event->isMasterRequest()) {
//      return;
//    }
//
//    $request = $event->getRequest();
////    $etag = $request->getETags();
//    $response = $event->getResponse();
//
//    $audience_cache = new CacheableMetadata();
//    $audience_cache->addCacheContexts(['cookies:audience_select_audience']);
////    $audience_cache->addCacheContexts(['audience']);
//    $audience_cache->setCacheMaxAge(0);
////    $audience_cache->setCacheTags(['audience']);
//    $response->addCacheableDependency($audience_cache);
////    $this->setExpiresNoCache($response);
////    $event->setResponse($response);
////    return $event;
//
//  }

//  /**
//   * Disable caching in ancient browsers and for HTTP/1.0 proxies and clients.
//   *
//   * HTTP/1.0 proxies do not support the Vary header, so prevent any caching by
//   * sending an Expires date in the past. HTTP/1.1 clients ignore the Expires
//   * header if a Cache-Control: max-age= directive is specified (see RFC 2616,
//   * section 14.9.3).
//   *
//   * @param \Symfony\Component\HttpFoundation\Response $response
//   *   A response object.
//   */
//  protected function setExpiresNoCache(Response $response) {
//    $exp = new \DateTime();
//    $exp->setTimestamp(REQUEST_TIME + 3);
////    $exp = date($exp);
//
//    $response->setExpires($exp);
//  }

  /**
   * Sets the 'audience' cache tag on AudienceEvent responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }
//    if( $response->st)
    $audience_cacheability = new CacheableMetadata();
    $audience_cacheability->setCacheTags(['audience']);
//
    $response->addCacheableDependency($audience_cacheability);
        $exp = new \DateTime();
    $exp->setTimestamp(REQUEST_TIME + 1);
    $response->setExpires($exp);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
//    $events[KernelEvents::REQUEST][] = array('checkForRedirection', 31);
    $events[KernelEvents::REQUEST][] = array('checkForRedirection', 33);

//    $events[KernelEvents::RESPONSE][] = array('onRespond', 10);
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }

//  /**
//   * @return \Drupal\audience_select\Service\AudienceManager
//   */
//  public function getAudienceManager(): \Drupal\audience_select\Service\AudienceManager {
//    return $this->AudienceManager;
//  }
}
