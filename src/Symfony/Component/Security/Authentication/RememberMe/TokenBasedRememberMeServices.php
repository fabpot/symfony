<?php

namespace Symfony\Component\Security\Authentication\RememberMe;

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
		if ($hash !== $this->generateCookieHash($username, $expires, $user->getPassword())) {
			throw new AuthenticationException('Token has invalid hash.');
		}
		
		if ($expires < time()) {
			throw new AuthenticationException('Token is already expired.');
		}
		
		return new RememberMeToken($user);
	}
	
	protected function generateCookieHash($username, $expires, $password)
	{
		if (0 === strlen($key)) {
			throw new \InvalidArgumentException('"security.authentication.rememberme.simplehash.key" must not be empty.');
		}
				
		return hash('sha256', $username.self::COOKIE_DELIMITER.$expires.self::COOKIE_DELIMITER.$password.self::COOKIE_DELIMITER.$this->key);
	}
}