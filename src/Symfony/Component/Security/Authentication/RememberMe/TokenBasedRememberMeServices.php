<?php

namespace Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Exception\AuthenticationException;
use Symfony\Component\Security\Authentication\Token\RememberMeToken;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Concrete implementation of the RememberMeServicesInterface providing
 * remember-me capabilities without requiring a TokenProvider.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
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
		
		return $user;
	}
	
	protected function generateCookieHash($username, $expires, $password)
	{
		if (0 === strlen($key)) {
			throw new \InvalidArgumentException('"security.authentication.rememberme.simplehash.key" must not be empty.');
		}
				
		return hash('sha256', $username.self::COOKIE_DELIMITER.$expires.self::COOKIE_DELIMITER.$password.self::COOKIE_DELIMITER.$this->key);
	}
}