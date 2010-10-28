<?php
namespace Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Exception\AuthenticationException;

class PersistentTokenBasedRememberMeServices extends RememberMeServices
{
	protected function processAutoLoginCookie($cookieParts)
	{
		if (count($cookieParts) !== 3) {
			throw new AuthenticationException('invalid cookie');
		}
		
		list($series, $tokenValue, $hash) = $cookieParts;
		$persistentToken = $this->tokenProvider->loadTokenBySeries($series);
		$user = $this->userProvider->loadUserByUsername($persistentToken->getUsername());
		
		if ($hash !== $this->generateCookieHash($series, $tokenValue, $user->getPassword(), $user->getSalt())) {
			throw new AuthenticationException('The hash of the cookie is invalid.');
		}
		
		if ($persistentToken->getTokenValue() !== $tokenValue) {
			$this->tokenProvider->deleteTokensBySeries($series);
			
			throw new CookieTheftException('This token was already used. The account is possibly compromised.');
		}
		
		return new RememberMeToken($user);
	}
	
	protected function generateRandomValue()
	{
		return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
	}
	
	protected function generateCookieHash($series, $tokenValue, $password, $salt)
	{
		return hash('sha256', sprintf('%s:%s:%s:%s', $series, $tokenValue, $password, $salt));
	}
}