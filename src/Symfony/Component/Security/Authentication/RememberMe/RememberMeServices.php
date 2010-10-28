<?php

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
namespace \Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\User\UserProviderInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;

abstract class RememberMeServices implements RememberMeServicesInterface
{
	const COOKIE_DELIMITER = ':';
	
	protected $userProvider;
	protected $options;
	protected $logger;
	protected $tokenProvider;
	
	public function __construct(UserProviderInterface $userProvider, array $options = array(), LoggerInterface $logger = null)
	{
		$this->userProvider = $userProvider;
		$this->options = $options;
		$this->logger = $logger;
	}
	
	public function setTokenProvider(TokenProviderInterface $tokenProvider)
	{
		$this->tokenProvider = $tokenProvider;
	}
	
	public function autoLogin(Request $request)
	{
		if (null === $cookie = $request->cookies->get($this->options['name'])) {
			return;
		}
		
		if (null !== $this->logger) {
			$this->logger->debug('Remember-me cookie detected.');
		}
		
		try {
			$cookieParts = $this->decodeCookie($cookie);
			$token = $this->processAutoLoginCookie($cookieParts);
			
			if (null !== $this->logger) {
				$this->logger->debug('Remember-me cookie accepted.');
			}
			
			return $token;
		} catch (AuthenticationException $failed) {
			$this->cancelCookie();
		}
	}
	
	/**
	 * 
	 * @param mixed $cookieParts
	 * @return TokenInterface
	 */
	abstract protected function processAutoLoginCookie($cookieParts);
	
	protected function decodeCookie($rawCookie)
	{
		return explode(self::COOKIE_DELIMITER, base64_decode($rawCookie));
	}
	
	public function onLoginFail() 
	{
		// TODO
	}
	
	public function onLoginSuccess(Request $request, Response $response, TokenInterface $token)
	{
		
	}
	
	protected function cancelCookie()
	{
		
	}
}