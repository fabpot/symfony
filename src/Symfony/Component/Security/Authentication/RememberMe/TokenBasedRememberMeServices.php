<?php

namespace Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
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
        
        return new RememberMeToken($user, $this->key);
    }
    
    protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        if (null === $user = $token->getUser()) {
            return;
        }
        
        $expires = time() + $this->options['lifetime'];
        $value = $this->generateCookieValue($user->getUsername(), $expires, $user->getPassword());
        
        $response->headers->setCookie($this->options['name'], $value, $this->options['domain'], $expires, $this->options['path'], $this->options['secure'], $this->options['httponly']);
    }
    
    protected function generateCookieValue($username, $expires, $password)
    {
        if (false !== strpos($username, self::COOKIE_DELIMITER)) {
            throw new \RuntimeException(sprintf('The username must not contain "%s".', self::COOKIE_DELIMITER));
        }
        
        return $this->encodeCookie(array($username, $expires, $this->generateCookieHash($username, $expires, $password)));
    }
    
    
    protected function generateCookieHash($username, $expires, $password)
    {
        if (0 === strlen($this->key)) {
            throw new \InvalidArgumentException('$key must not be empty.');
        }
                
        return hash('sha256', $username.self::COOKIE_DELIMITER.$expires.self::COOKIE_DELIMITER.$password.self::COOKIE_DELIMITER.$this->key);
    }
}