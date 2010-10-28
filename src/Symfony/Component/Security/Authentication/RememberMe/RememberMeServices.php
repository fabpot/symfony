<?php

namespace Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\User\UserProviderInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class implementing the RememberMeServicesInterface
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
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
			$user = $this->processAutoLoginCookie($cookieParts);
			
			if (null !== $this->logger) {
				$this->logger->debug('Remember-me cookie accepted.');
			}
			
			return new RememberMeToken($user, $this->key);
		} catch (AuthenticationException $failed) {
			$this->cancelCookie();
		}
	}
	
	/**
	 * 
	 * @param array $cookieParts
	 * @return AccountInterface
	 */
	abstract protected function processAutoLoginCookie($cookieParts);
	
	/**
	 * @param string $rawCookie
	 * @return array
	 */
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