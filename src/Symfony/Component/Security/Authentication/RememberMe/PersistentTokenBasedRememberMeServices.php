<?php
use Symfony\Component\Security\Exception\AuthenticationException;
namespace \Symfony\Component\Security\Authentication\RememberMe;

class PersistentTokenBasedRememberMeServices extends RememberMeServices
{
	public function autoLogin(Request $request)
	{
		if (null === $cookie = $request->cookies->get($this->options['name'])) {
			return;
		}
		
		if (3 !== count($parts = explode(':', base64_decode($cookie)))) {
			throw new AuthenticationException('invalid cookie');
		}
		
		list($series, $tokenValue, $hash) = $parts;
		$persistentToken = $this->tokenProvider->loadTokenBySeries($series);
		$user = $this->userProvider->loadUserByUsername($persistentToken->getUsername());
		
		if ($hash !== $this->generateCookieHash($series, $tokenValue, $user->getPassword(), $user->getSalt())) {
			throw new AuthenticationException('The hash of the cookie is invalid.');
		}
		
		if ($persistentToken->getTokenValue() !== $tokenValue) {
			$this->tokenProvider->deleteTokensByUsername($persistentToken->getUsername());
			
			throw new AuthenticationException('This token was already used. The account is possibly compromised.');
		}
		
		
		$authenticationToken = $this->authenticationManager->authenticate(new RememberMeToken($user));
		
		$newTokenValue = $this->generateRandomValue();
		$this->tokenProvider->updateToken($series, $newTokenValue, new Date());
		// TODO: add the updated cookie to the response
		
		return $authenticationToken;
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