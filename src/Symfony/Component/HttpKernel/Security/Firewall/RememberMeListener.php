<?php

namespace Symfony\Component\HttpKernel\Security\Firewall;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Exception\AuthenticationException;
use Symfony\Component\Security\Exception\CookieTheftException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\SecurityContext;
use Symfony\Component\HttpKernel\Security\RememberMe\RememberMeServicesInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * RememberMeListener implements authentication capabilities via a cookie
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberMeListener implements ListenerInterface
{
    protected $securityContext;
    protected $rememberMeServices;
    protected $authenticationManager;
    protected $logger;
    protected $lastState;

    /**
     * Constructor
     *
     * @param SecurityContext $securityContext
     * @param RememberMeServicesInterface $rememberMeServices
     * @param AuthenticationManagerInterface $authenticationManager
     * @param LoggerInterface $logger
     */
    public function __construct(SecurityContext $securityContext, RememberMeServicesInterface $rememberMeServices, AuthenticationManagerInterface $authenticationManager, LoggerInterface $logger = null)
    {
        $this->securityContext = $securityContext;
        $this->rememberMeServices = $rememberMeServices;
        $this->authenticationManager = $authenticationManager;
        $this->logger = $logger;
    }

    /**
     * Listen to core.security, and core.response event
     *
     * @param EventDispatcher $dispatcher An EventDispatcher instance
     * @param integer         $priority   The priority
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->connect('core.security', array($this, 'checkCookies'), 0);
        $dispatcher->connect('core.response', array($this, 'updateCookies'), 0);
    }

    /**
     * {@inheritDoc}
     */
    public function unregister(EventDispatcher $dispatcher)
    {
        $dispatcher->disconnect('core.response', array($this, 'updateCookies'));
    }

    /**
     * Returns the security context
     * @return SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * Returns the RememberMeServices implementation
     * @return RememberMeServicesInterface
     */
    public function getRememberMeServices()
    {
        return $this->rememberMeServices;
    }

    /**
     * Returns the AuthenticationManagerInterface implementation
     * @return AuthenticationManagerInterface
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }

    /**
     * Returns the LoggerInterface implementation
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Handles remember-me cookie based authentication.
     *
     * @param Event $event An Event instance
     */
    public function checkCookies(Event $event)
    {
        $this->lastState = null;

        if (null !== $this->securityContext->getToken()) {
            return;
        }

        try {
            if (null === $token = $this->rememberMeServices->autoLogin($event->get('request'))) {
                return;
            }

            try {
                if (null === $token = $this->authenticationManager->authenticate($token)) {
                    return;
                }

                $this->securityContext->setToken($token);

                if (null !== $this->logger) {
                    $this->logger->debug('SecurityContext populated with remember-me token.');
                }

                $this->lastState = $token;
            } catch (AuthenticationException $failed) {
                if (null !== $this->logger) {
                    $this->logger->debug(
                        'SecurityContext not populated with remember-me token as the'
                       .' AuthenticationManager rejected the AuthenticationToken returned'
                       .' by the RememberMeServices: '.$failed->getMessage()
                    );
                }

                $this->lastState = $failed;
            }
        } catch (AuthenticationException $cookieInvalid) {
            $this->lastState = $cookieInvalid;

            if (null !== $this->logger) {
                $this->logger->debug('The presented cookie was invalid: '.$cookieInvalid->getMessage());
            }

            // silently ignore everything except a cookie theft exception
            if ($cookieInvalid instanceof CookieTheftException) {
                throw $cookieInvalid;
            }
        }
    }

    /**
     * Update cookies
     * @param Event $event
     */
    public function updateCookies(Event $event, Response $response)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->get('request_type')) {
            return $response;
        }

        if ($this->lastState instanceof TokenInterface) {
            $this->rememberMeServices->loginSuccess($event->get('request'), $response, $this->lastState);
        } else if ($this->lastState instanceof AuthenticationException) {
            $this->rememberMeServices->loginFail($event->get('request'), $response);
        }

        return $response;
    }
}