<?php

namespace \Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Exception\AuthenticationException;
use Symfony\Component\Security\Authentication\Token\RememberMeToken;

class TokenBasedRememberMeServices extends RememberMeServices
{
	public function autoLogin(Request $request)
	{
		if (null === $cookie = $request->cookies->get($this->options['name'])) {
			return;
		}
		
		if (3 !== count($parts = explode(':', base64_decode($cookie)))) {
			throw new AuthenticationException('Invalid remember me token.');
		}
		
		list($username, $expires, $hash) = $parts;
		$user = $this->userProvider->loadUserByUsername($username);
		
		// TODO: Do we need constant-time comparison here?
		if ($hash !== $this->generateCookieHash($username, $expires, $user->getPassword(), $user->getSalt())) {
			throw new AuthenticationException('Token has invalid hash.');
		}
		
		if ($expires < time()) {
			throw new AuthenticationException('Token is already expired.');
		}
		
		return $this->authenticationManager->authenticate(new RememberMeToken($user));
	}
	
	protected function generateCookieHash($username, $expires, $password, $salt) 
	{
		return hash('sha256', sprintf('%s:%d:%s:%s', $username, $expires, $password, $salt));
	}
}