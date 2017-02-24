<?php

namespace Drupal\pmmi_sso\Controller;

use Drupal\pmmi_sso\Exception\PMMISSOLoginException;
use Drupal\pmmi_sso\Exception\PMMISSOSloException;
use Drupal\pmmi_sso\Service\PMMISSOHelper;
use Drupal\pmmi_sso\Exception\PMMISSOValidateException;
use Drupal\pmmi_sso\Service\PMMISSOUserManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pmmi_sso\Service\PMMISSOValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\pmmi_sso\Service\PMMISSOLogout;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ServiceController.
 */
class PMMISSOServiceController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Used for misc. services required.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOHelper
   */
  protected $ssoHelper;

  /**
   * Used to validate PMMI SSO service token.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOValidator
   */
  protected $ssoValidator;

  /**
   * Used to log a user in after they've been validated.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOUserManager
   */
  protected $ssoUserManager;

  /**
   * Used to log a user out due to a single log out request.
   *
   * @var \Drupal\pmmi_sso\Service\PMMISSOLogout
   */
  protected $ssoLogout;

  /**
   * Used to retrieve request parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Used to generate redirect URLs.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param PMMISSOHelper $sso_helper
   *   The PMMI SSO Helper service.
   * @param PMMISSOValidator $sso_validator
   *   The PMMI SSO Validator service.
   * @param PMMISSOUserManager $sso_user_manager
   *   The PMMI SSO User Manager service.
   * @param PMMISSOLogout $sso_logout
   *   The PMMI SSO Logout service.
   * @param UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(
    PMMISSOHelper $sso_helper,
    PMMISSOValidator $sso_validator,
    PMMISSOUserManager $sso_user_manager,
//    PMMISSOLogout $sso_logout,
    RequestStack $request_stack,
    UrlGeneratorInterface $url_generator
  ) {
    $this->ssoHelper = $sso_helper;
    $this->ssoValidator = $sso_validator;
    $this->ssoUserManager = $sso_user_manager;
//    $this->ssoLogout = $sso_logout;
    $this->requestStack = $request_stack;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pmmi_sso.helper'),
      $container->get('pmmi_sso.validator'),
      $container->get('pmmi_sso.user_manager'),
//      $container->get('pmmi_sso.logout'),
      $container->get('request_stack'),
      $container->get('url_generator')
    );
  }

  /**
   * Main point of communication between PMMI SSO server and the Drupal site.
   *
   * The path that this controller/action handle are always set to the "service"
   * url when authenticating with the PMMI SSO server, so PMMI SSO server
   * communicates back to the Drupal site using this controller action. That's
   * why there's so much going on in here - it needs to process a few different
   * types of requests.
   */
  public function handle() {
    $request = $this->requestStack->getCurrentRequest();

//    // First, check if this is a single-log-out (SLO) request from the server.
//    if ($request->request->has('logoutRequest')) {
//      try {
//        $this->ssoLogout->handleSlo($request->request->get('logoutRequest'));
//      }
//      catch (PMMISSOSloException $e) {
//        $this->ssoHelper->log($e->getMessage());
//      }
//      // Always return a 200 code. PMMI SSO Server doesn’t care either way what
//      // happens here, since it is a fire-and-forget approach taken.
//      return Response::create('', 200);
//    }

    // We will be redirecting the user below. To prevent the PMMISSOSubscriber
    // from initiating an automatic authentiation on the that request (like
    // forced auth or gateway auth) and potentially creating an authentication
    // loop, we set a session variable instructing the PMMISSOSubscriber skip
    // auto auth for that request.
    $request->getSession()->set('sso_temp_disable_auto_auth', TRUE);

    /* If there is no token parameter on the request, the browser either:
     * (a) is returning from a gateway request to the PMMI SSO server in which
     *     the user was not already authenticated to PMMI SSO, so there is no
     *     service token to validate and nothing to do.
     * (b) has hit this URL for some other reason (crawler, curiosity, etc)
     *     and there is nothing to do.
     * In either case, we just want to redirect them away from this controller.
     */
    if (!$request->query->has('ct')) {
      $this->ssoHelper->log("No token detected, move along.");
      $this->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }

    // There is a token present, meaning PMMISSO server has returned the browser
    // to the Drupal site so we can authenticate the user locally using the
    // token.
    $token = $request->query->get('ct');
    // Our PMMI SSO service will need to reconstruct the original service URL
    // when validating the token. We always know what the base URL for
    // the service URL (it's this page), but there may be some query params
    // attached as well (like a destination param) that we need to pass in
    // as well. So, detach the token param, and pass the rest off.
    $service_params = $request->query->all();
    unset($service_params['ct']);
    try {
      $sso_validation_info = $this->ssoValidator->validateToken($token, FALSE);
    }
    catch (PMMISSOValidateException $e) {
      // Validation failed, redirect to homepage and set message.
      $this->ssoHelper->log($e->getMessage());
      $this->setMessage($this->t('There was a problem validating your login, please contact a site administrator.'), 'error');
      $this->handleReturnToParameter($request);
      return RedirectResponse::create($this->urlGenerator->generate('<front>'));
    }

    // Now that the token has been validated, we can use the information from
    // validation request to authenticate the user locally on the Drupal site.
    try {
      $this->ssoUserManager->login($sso_validation_info, $token);
//      if ($this->ssoHelper->isProxy() && $sso_validation_info->getPgt()) {
//        $this->ssoHelper->log("Storing PGT information for this session.");
//        $this->ssoHelper->storePgtSession($sso_validation_info->getPgt());
//      }
      $this->setMessage($this->t('You have been logged in.'));
    }
    catch (PMMISSOLoginException $e) {
      $this->ssoHelper->log($e->getMessage());
      $this->setMessage($this->t('There was a problem logging in, please contact a site administrator.'), 'error');
    }

    // And finally redirect the user to the homepage, or so a specific
    // destination found in the destination param (like the page they were on
    // prior to initiating authentication).
    $this->handleReturnToParameter($request);
    return RedirectResponse::create($this->urlGenerator->generate('<front>'));
  }

  /**
   * Converts a "returnto" query param to a "destination" query param.
   *
   * The original service URL for PMMI SSO server may contain a "returnto" query
   * parameter that was placed there to redirect a user to specific page after
   * logging in with PMMI SSO.
   *
   * Drupal has a built in mechanism for doing this, by instead using a
   * "destination" parameter in the URL. Anytime there's a RedirectResponse
   * returned, RedirectResponseSubscriber looks for the destination param and
   * will redirect a user there instead.
   *
   * We cannot use this built in method when constructing the service URL,
   * because when we redirect to the PMMI SSO server for login, Drupal would see
   * our destination parameter in the URL and redirect there instead of PMMISSO.
   *
   * However, when we redirect the user after a login success / failure,
   * we can then convert it back to a "destination" parameter and let Drupal
   * do it's thing when redirecting.
   *
   * @param Request $request
   *   The Symfony request object.
   */
  private function handleReturnToParameter(Request $request) {
    if ($request->query->has('returnto')) {
      $this->ssoHelper->log("Converting returnto parameter to destination.");
      $request->query->set('destination', $request->query->get('returnto'));
    }
  }

  /**
   * Encapsulation of drupal_set_message.
   *
   * See https://www.drupal.org/node/2278383 for discussion about converting
   * drupal_set_message to a service. In the meantime, in order to unit test
   * the error handling here, we have to encapsulate the call in a method.
   *
   * @param string $message
   *   The message text to set.
   * @param string $type
   *   The message type.
   * @param bool $repeat
   *   Whether identical messages should all be shown.
   *
   * @codeCoverageIgnore
   */
  public function setMessage($message, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

}
