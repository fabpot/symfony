<?php
namespace \Symfony\Component\HttpKernel\Security\Firewall;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;

/**
 *  
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class RememberMeListener 
{
	protected $securityContext;
	protected $rememberMeServices;
	protected $logger;
	
	/**
	 * Constructor
	 * 
	 * @param SecurityContext $securityContext
	 * @param AuthenticationManagerInterface $authenticationManager
	 * @param array $options
	 * @param LoggerInterface $logger
	 */
	public function __construct(SecurityContext $securityContext, RememberMeServicesInterface $rememberMeServices, LoggerInterface $logger = null)
	{
		$this->securityContext = $securityContext;
		$this->rememberMeServices = $rememberMeServices;
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

        try {
        	if (null === $token = $this->rememberMeServices->autoLogin($request)) {
        		return;
        	}
        	
        	// TODO
        	$this->rememberMeServices->onLoginSuccess();
        } catch (AuthenticationException $failed) {
        	// TODO
        	$this->rememberMeServices->onLoginFail();
        	
            $response = $this->onFailure($request, $failed);

        	$event->setReturnValue($response);
        }

        return true;
    }
}