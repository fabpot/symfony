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

/**
 *  
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberMeListener 
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
    public function register(EventDispatcher $dispatcher, $readPriority = 0, $writePriority = -1)
    {
        $dispatcher->connect('core.security', array($this, 'checkCookies'), $readPriority);
        $dispatcher->connect('core.response', array($this, 'updateCookies'), $writePriority);
    }
    
    /**
     * Handles remember-me cookie based authentication.
     *
     * @param Event $event An Event instance
     */
    public function checkCookies(Event $event)
    {
        $request = $event->getParameter('request');

        $this->lastState = null;
        
        if (null !== $this->securityContext->getToken()) {
            return;
        }
        
        try {
            if (null === $token = $this->rememberMeServices->autoLogin($request)) {
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
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getParameter('request_type')) {
            return $response;
        }
        
        if ($this->lastState instanceof TokenInterface) {
            $this->rememberMeServices->loginSuccess($event->getParameter('request'), $response, $this->lastState);
        } else if ($this->lastState instanceof AuthenticationException) {
            $this->rememberMeServices->loginFail($event->getParameter('request'), $response);
        }
        
        return $response;
    }
}