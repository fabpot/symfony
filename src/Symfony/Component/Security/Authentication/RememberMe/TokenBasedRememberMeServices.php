<?php

namespace \Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Exception\AuthenticationException;
use Symfony\Component\Security\Authentication\Token\RememberMeToken;

class TokenBasedRememberMeServices extends RememberMeServices
{
	protected function processAutoLoginCookie($cookieParts)
	{
		if (count($cookieParts) !== 3) {
			throw new AuthenticationException('Invalid remember me token.');
		}
		
		list($username, $expires, $hash) = $cookieParts;
		$user = $this->userProvider->loadUserByUsername($username);
		
		// TODO: Do we need constant-time comparison here?
		if ($hash !== $this->generateCookieHash($username, $expires, $user->getPassword(), $user->getSalt())) {
			throw new AuthenticationException('Token has invalid hash.');
		}
		
		if ($expires < time()) {
			throw new AuthenticationException('Token is already expired.');
		}
		
		return new RememberMeToken($user);
	}
	
	protected function generateCookieHash($username, $expires, $password, $salt) 
	{
		return hash('sha256', sprintf('%s:%d:%s:%s', $username, $expires, $password, $salt));
	}
}