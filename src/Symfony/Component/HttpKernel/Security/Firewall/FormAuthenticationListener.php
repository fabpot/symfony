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
    protected $checkPath;
    protected $logger;

    /**
     * Constructor.
     *
     * @param SecurityContext                $securityContext       A SecurityContext instance
     * @param AuthenticationManagerInterface $authenticationManager An AuthenticationManagerInterface instance
     * @param array                          $options               An array of options
     * @param LoggerInterface                $logger                A LoggerInterface instance
     */
    public function __construct(SecurityContext $securityContext, AuthenticationManagerInterface $authenticationManager, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, $checkPath, LoggerInterface $logger = null)
    {
        if (empty($checkPath)) {
            throw new \InvalidArgumentException('$checkPath cannot be empty.');
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->checkPath = $checkPath;
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

        if ($this->checkPath !== $request->getPathInfo()) {
            return;
        }

        try {
            if (null === $token = $this->attemptAuthentication($request)) {
                return;
            }

            $response = $this->onSuccess($event, $request, $token);
        } catch (AuthenticationException $failed) {
            $response = $this->onFailure($event->getSubject(), $request, $failed);
        }

        $event->setReturnValue($response);

        return true;
    }

    protected function onFailure($kernel, Request $request, \Exception $failed)
    {
        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Authentication request failed: %s', $failed->getMessage()));
        }

        $this->securityContext->setToken(null);

        return $this->failureHandler->onAuthenticationFailure($event, $request, $failed);
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

        return $this->successHandler->onAuthenticationSuccess($request, $token);
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
