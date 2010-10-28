<?php

namespace Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\User\UserProviderInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

abstract class RememberMeServices implements RememberMeServicesInterface
{
	const COOKIE_DELIMITER = ':';
	
	protected $userProvider;
	protected $options;
	protected $logger;
	protected $tokenProvider;
	protected $key;
	
	public function __construct(UserProviderInterface $userProvider, array $options = array(), LoggerInterface $logger = null)
	{
		$this->userProvider = $userProvider;
		$this->options = $options;
		$this->logger = $logger;
	}
	
	public function setKey($key)
	{
		$this->key = $key;
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
		// TODO: Invalidate any and all remember-me tokens
	}
	
	public function onLoginSuccess(Request $request, Response $response, TokenInterface $token)
	{
		// TODO: Set the remember-me token if requested (this should be called after
		//       an interactive authentication was successful; e.g. after the form-login)
	}
	
	protected function cancelCookie()
	{
		
	}
}