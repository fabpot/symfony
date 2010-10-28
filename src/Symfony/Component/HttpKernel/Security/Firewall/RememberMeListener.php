<?php
namespace Symfony\Component\HttpKernel\Security\Firewall;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\SecurityContext;
use Symfony\Component\Security\Authentication\RememberMe\RememberMeServicesInterface;

/**
 *  
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberMeListener 
{
	protected $securityContext;
	protected $rememberMeServices;
	protected $authenticationManager;
	protected $options;
	protected $logger;
	
	/**
	 * Constructor
	 * 
	 * @param SecurityContext $securityContext
	 * @param RememberMeServicesInterface $rememberMeServices
	 * @param AuthenticationManagerInterface $authenticationManager
	 * @param array $options
	 * @param LoggerInterface $logger
	 */
	public function __construct(SecurityContext $securityContext, RememberMeServicesInterface $rememberMeServices, AuthenticationManagerInterface $authenticationManager, array $options, LoggerInterface $logger = null)
	{
		$this->securityContext = $securityContext;
		$this->rememberMeServices = $rememberMeServices;
		$this->authenticationManager = $authenticationManager;
		$this->options = $options;
		$this->logger = $logger;
	}
	
    /**
     * Listen to core.security event
     *
     * @param EventDispatcher $dispatcher An EventDispatcher instance
     * @param integer         $priority   The priority
     */
    public function register(EventDispatcher $dispatcher, $priority = 0)
    {
        $dispatcher->connect('core.security', array($this, 'handle'), $priority);
        $dispatcher->connect('core.response', array($this, 'updateCookies'), $priority);
    }
	
    /**
     * Handles remember-me cookie based authentication.
     *
     * @param Event $event An Event instance
     */
    public function handle(Event $event)
    {
        $request = $event->getParameter('request');

        if (null !== $this->securityContext->getToken()) {
        	return;
        }
        
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
        } catch (AuthenticationException $failed) {
        	if (null !== $this->logger) {
        		$this->logger->debug(
        			'SecurityContext not populated with remember-me token as the'
        		   .' AuthenticationManager rejected the AuthenticationToken returned'
        		   .' by the RememberMeServices: '.$failed->getMessage()
        		);
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
        
        
    }
}