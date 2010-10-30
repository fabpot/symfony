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
            throw new AuthenticationException('Invalid value.');
        }
        
        list($username, $expires, $hash) = $cookieParts;
        $user = $this->userProvider->loadUserByUsername($username);
        
        if (true !== $this->compareHashes($hash, $this->generateCookieHash($username, $expires, $user->getPassword()))) {
            throw new AuthenticationException('Invalid hash.');
        }
        
        if ($expires < time()) {
            throw new AuthenticationException('Already expired.');
        }
        
        return new RememberMeToken($user, $this->key);
    }
    
    /**
     * Compares two hashes using a constant-time algorithm to avoid (remote)
     * timing attacks.
     *
     * This is the same implementation as used in the BasePasswordEncoder.
     *
     * @param string $hash1 The first hash
     * @param string $hash2 The second hash
     *
     * @return Boolean true if the two hashes are the same, false otherwise
     */
    protected function compareHashes($hash1, $hash2)
    {
        if (strlen($hash1) !== strlen($hash2)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($hash1); $i++) {
            $result |= ord($hash1[$i]) ^ ord($hash2[$i]);
        }

        return 0 === $result;
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
            throw new \RuntimeException('"security.rememberme.key" must not be empty.');
        }
                
        return hash('sha256', $username.self::COOKIE_DELIMITER.$expires.self::COOKIE_DELIMITER.$password.self::COOKIE_DELIMITER.$this->key);
    }
}