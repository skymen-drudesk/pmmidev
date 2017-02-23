<?php

namespace Drupal\pmmi_sso\Controller;

use Drupal\pmmi_sso\PMMISSORedirectData;
use Drupal\pmmi_sso\Service\PMMISSORedirector;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PMMISSOForceLoginController.
 */
class PMMISSOForceLoginController implements ContainerInjectionInterface {
  /**
   * The PMMI SSO helper to get config settings from.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSORedirector
   */
  protected $ssoRedirector;

  /**
   * Used to get query string parameters from the request.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param PMMISSORedirector $sso_redirector
   *   The PMMISSO Redirector service.
   * @param RequestStack $request_stack
   *   Symfony request stack.
   */
  public function __construct(PMMISSORedirector $sso_redirector, RequestStack $request_stack) {
    $this->ssoRedirector = $sso_redirector;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('pmmi_sso.redirector'), $container->get('request_stack'));
  }

  /**
   * Handles a page request for our forced login route.
   */
  public function forceLogin() {
    // TODO: What if PMMISSO is not configured? need to handle that case.
    $service_url_query_params = $this->requestStack->getCurrentRequest()->query->all();
    $sso_redirect_data = new PMMISSORedirectData($service_url_query_params);
    $sso_redirect_data->setIsCacheable(TRUE);
    return $this->ssoRedirector->buildRedirectResponse($sso_redirect_data, TRUE);
  }

}
