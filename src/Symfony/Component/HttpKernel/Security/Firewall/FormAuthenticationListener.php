<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Security\Firewall;

use Symfony\Component\HttpKernel\Security\Session\SessionAuthenticationStrategyInterface;

use Symfony\Component\HttpKernel\Security\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\HttpKernel\Security\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\SecurityContext;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Authentication\Token\TokenInterface;

/**
 * FormAuthenticationListener implements authentication via a form.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class FormAuthenticationListener
{
    protected $securityContext;
    protected $successHandler;
    protected $failureHandler;
    protected $authenticationManager;
    protected $eventDispatcher;
    protected $sessionStrategy;
    protected $options;
    protected $logger;

    /**
     * Constructor.
     *
     * @param SecurityContext                $securityContext       A SecurityContext instance
     * @param AuthenticationManagerInterface $authenticationManager An AuthenticationManagerInterface instance
     * @param array                          $options               An array of options
     * @param LoggerInterface                $logger                A LoggerInterface instance
     */
    public function __construct(SecurityContext $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, array $options = array(), AuthenticationSuccessHandlerInterface $successHandler = null, AuthenticationFailureHandlerInterface $failureHandler = null, LoggerInterface $logger = null)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->sessionStrategy = $sessionStrategy;
        $this->options = array_merge(array(
            'check_path'										 => '/login_check',
            'login_path'                     => '/login',
            'always_use_default_target_path' => false,
            'default_target_path'            => '/',
            'target_path_parameter'          => '_target_path',
            'use_referer'                    => false,
            'failure_path'                   => null,
            'failure_forward'                => false,
        ), $options);
        $this->logger = $logger;
    }

    /**
     *
     *
     * @param EventDispatcher $dispatcher An EventDispatcher instance
     * @param integer         $priority   The priority
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->connect('core.security', array($this, 'handle'), 0);

        $this->eventDispatcher = $dispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function unregister(EventDispatcher $dispatcher)
    {
    }

    /**
     * Handles form based authentication.
     *
     * @param Event $event An Event instance
     */
    public function handle(Event $event)
    {
        $request = $event->get('request');

        if ($this->options['check_path'] !== $request->getPathInfo()) {
            return;
        }

        try {
            if (null === $token = $this->attemptAuthentication($request)) {
                return;
            }

            $this->sessionStrategy->onAuthentication($request, $token);

            $response = $this->onSuccess($event, $request, $token);
        } catch (AuthenticationException $failed) {
            $response = $this->onFailure($event, $request, $failed);
        }

        $event->setReturnValue($response);

        return true;
    }

    protected function onFailure($event, Request $request, \Exception $failed)
    {
        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Authentication request failed: %s', $failed->getMessage()));
        }

        $this->securityContext->setToken(null);

        if (null !== $this->failureHandler) {
            return $this->failureHandler->onAuthenticationFailure($event, $request, $failed);
        }

        if (null === $this->options['failure_path']) {
            $this->options['failure_path'] = $this->options['login_path'];
        }

        if ($this->options['failure_forward']) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Forwarding to %s', $this->options['failure_path']));
            }

            $subRequest = Request::create($this->options['failure_path']);
            $subRequest->attributes->set(SecurityContext::AUTHENTICATION_ERROR, $failed->getMessage());

            return $event->getSubject()->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } else {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Redirecting to %s', $this->options['failure_path']));
            }

            $request->getSession()->set(SecurityContext::AUTHENTICATION_ERROR, $failed->getMessage());

            $response = new Response();
            $response->setRedirect(0 !== strpos($this->options['failure_path'], 'http') ? $request->getUriForPath($this->options['failure_path']) : $this->options['failure_path'], 302);

            return $response;
        }
    }

    protected function onSuccess(Event $event, Request $request, TokenInterface $token)
    {
        if (null !== $this->logger) {
            $this->logger->debug('User has been authenticated successfully');
        }

        $this->securityContext->setToken($token);

        $session = $request->getSession();
        $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        $session->remove(SecurityContext::LAST_USERNAME);

        $this->eventDispatcher->notify(new Event($this, 'security.login_success', array('request' => $request, 'token' => $token)));

        if (null !== $this->successHandler) {
            return $this->successHandler->onAuthenticationSuccess($request, $token);
        }

        $response = new Response();
        $path = $this->determineTargetUrl($request);
        $response->setRedirect(0 !== strpos($path, 'http') ? $request->getUriForPath($path) : $path, 302);

        return $response;
    }

    /**
     * Builds the target URL according to the defined options.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        if ($targetUrl = $request->get($this->options['target_path_parameter'])) {
            return $targetUrl;
        }

        $session = $request->getSession();
        if ($targetUrl = $session->get('_security.target_path')) {
            $session->remove('_security.target_path');

            return $targetUrl;
        }

        if ($this->options['use_referer'] && $targetUrl = $request->headers->get('Referer')) {
            return $targetUrl;
        }

        return $this->options['default_target_path'];
    }

    /**
     * Performs authentication.
     *
     * @param  Request $request A Request instance
     *
     * @return TokenInterface The authenticated token, or null if full authentication is not possible
     *
     * @throws AuthenticationException if the authentication fails
     */
    abstract protected function attemptAuthentication(Request $request);
}
