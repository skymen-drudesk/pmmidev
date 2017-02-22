<?php

namespace Drupal\pmmi_personify_sso\Controller;

use Drupal\pmmi_personify_sso\Service\PMMIPersonifySSOManager;
use Exception;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PMMIPersonifySSOController.
 *
 * @package Drupal\pmmi_personify_sso\Controller
 */
class PMMIPersonifySSOController extends ControllerBase {

  /**
   * The Personify SSO Manager.
   *
   * @var \Drupal\pmmi_personify_sso\Service\PMMIPersonifySSOManager
   */
  protected $ssoManager;

  /**
   * Constructor for PMMIPersonifySSOController.
   *
   * @param RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\pmmi_personify_sso\Service\PMMIPersonifySSOManager $sso_manager
   *   The samlauth SAML service.
   */
  public function __construct(
    RequestStack $request_stack,
    PMMIPersonifySSOManager $sso_manager
  ) {
    $this->requestStack = $request_stack;
    $this->ssoManager = $sso_manager;
  }

  /**
   * Factory method for dependency injection container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The interface implemented by service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('pmmi_personify_sso.manager')
    );
  }

  /**
   * Initiates a Personify SSO authentication flow.
   *
   * This should redirect to the Login service on the IDP and then to our ACS.
   */
  public function login() {
    $this->ssoManager->login();
  }

  /**
   * Initiate a Personify SSO logout flow.
   *
   * This should redirect to the SLS service on the IDP and then to our SLS.
   */
  public function logout() {
    $this->ssoManager->logout();
  }

//  /**
//   * Displays service provider metadata XML for iDP autoconfiguration.
//   *
//   * @return \Symfony\Component\HttpFoundation\Response
//   */
//  public function metadata() {
//    $metadata = $this->ssoManager->getMetadata();
//    $response = new Response($metadata, 200);
//    $response->headers->set('Content-Type', 'text/xml');
//    return $response;
//  }
//
//  /**
//   * Attribute Consumer Service.
//   *
//   * This is usually the second step in the authentication flow; the Login
//   * service on the IDP should redirect (or: execute a POST request to) here.
//   *
//   * @return \Symfony\Component\HttpFoundation\RedirectResponse
//   */
//  public function acs() {
//    try {
//      $this->ssoManager->acs();
//    }
//    catch (Exception $e) {
//      drupal_set_message($e->getMessage(), 'error');
//      return new RedirectResponse('/');
//    }
//
//    $route = $this->ssoManager->getPostLoginDestination();
//    $url = \Drupal::urlGenerator()->generateFromRoute($route);
//    return new RedirectResponse($url);
//  }
//
//  /**
//   * Single Logout Service.
//   *
//   * This is usually the second step in the logout flow; the SLS service on the
//   * IDP should redirect here.
//   *
//   * @return \Symfony\Component\HttpFoundation\RedirectResponse
//   *
//   * @todo we already called user_logout() at the start of the logout
//   *   procedure i.e. at logout(). The route that leads here is only accessible
//   *   for authenticated user. So in a logout flow where the user starts at
//   *   /saml/logout, this will never be executed and the user gets an "Access
//   *   denied" message when returning to /saml/sls; this code is never executed.
//   *   We should probably change the access rights and do more checking inside
//   *   this function whether we should still log out.
//   */
//  public function sls() {
//    $this->saml->sls();
//
//    $route = $this->saml->getPostLogoutDestination();
//    $url = \Drupal::urlGenerator()->generateFromRoute($route);
//    return new RedirectResponse($url);
//  }

}
