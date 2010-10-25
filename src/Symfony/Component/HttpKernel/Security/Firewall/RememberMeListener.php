<?php
namespace \Symfony\Component\HttpKernel\Security\Firewall;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;

abstract class RememberMeListener 
{
	protected $securityContext;
	protected $authenticationManager;
	protected $options;
	protected $logger;
	
	/**
	 * Constructor
	 * 
	 * @param SecurityContext $securityContext
	 * @param AuthenticationManagerInterface $authenticationManager
	 * @param array $options
	 * @param LoggerInterface $logger
	 */
	public function __construct(SecurityContext $securityContext, AuthenticationManagerInterface $authenticationManager, array $options = array(), LoggerInterface $logger = null)
	{
		$this->securityContext = $securityContext;
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
    }
	
    /**
     * Handles form based authentication.
     *
     * @param Event $event An Event instance
     */
    public function handle(Event $event)
    {
        $request = $event->getParameter('request');

        if (null === $cookie = $request->cookies->get($this->options['name'])) {
        	return;
        }
        
        $cookie = base64_decode($cookie);
        if (false === $usernameEnd = strpos($cookie, ':'))
        {
        	return;
        }
        
        $username = substr($cookie, 0, $usernameEnd);
        $data = substr($cookie, $usernameEnd + 1);
        
        try {
        	$token = $this->authenticationManager->authenticate(new SimpleHashRememberMeToken($username, $data));
        	if (null === $token) {
        		return;
        	}
        } catch (AuthenticationException $failed) {
            $response = $this->onFailure($request, $failed);

        	$event->setReturnValue($response);
        }

        return true;
    }
}