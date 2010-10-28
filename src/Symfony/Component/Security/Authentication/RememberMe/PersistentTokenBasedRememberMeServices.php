<?php
namespace Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Exception\AuthenticationException;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Concrete implementation of the RememberMeServicesInterface which needs
 * an implementation of TokenProviderInterface for providing remember-me
 * capabilities.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
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
			$this->tokenProvider->deleteTokenBySeries($series);
			
			throw new CookieTheftException('This token was already used. The account is possibly compromised.');
		}
		
		return $user;
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