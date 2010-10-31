<?php
namespace Symfony\Component\HttpKernel\Security\RememberMe;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Exception\AuthenticationException;
use Symfony\Component\Security\Exception\CookieTheftException;

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
    /**
     * {@inheritDoc}
     */
    protected function processAutoLoginCookie(array $cookieParts, Request $request)
    {
        if (count($cookieParts) !== 2) {
            throw new AuthenticationException('invalid cookie');
        }
        
        list($series, $tokenValue) = $cookieParts;
        $persistentToken = $this->tokenProvider->loadTokenBySeries($series);
        $user = $this->userProvider->loadUserByUsername($persistentToken->getUsername());
        
        if ($persistentToken->getTokenValue() !== $tokenValue) {
            $this->tokenProvider->deleteTokenBySeries($series);
            
            throw new CookieTheftException('This token was already used. The account is possibly compromised.');
        }
        
        if ($persistentToken->getLastUsed()->getTimestamp() + $this->options['lifetime'] < time()) {
            throw new AuthenticationException('The cookie has expired.');
        }
        
        $authenticationToken = new RememberMeToken($user, $this->key);
        $authenticationToken->setPersistentToken($persistentToken);
        
        return $authenticationToken;
    }
    
    /**
     * {@inheritDoc}
     */
    protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        if ($token instanceof RememberMeToken && null !== $persistentToken = $token->getPersistentToken()) {
            $newTokenValue = $this->generateRandomValue();
            $this->tokenProvider->updateToken($persistentToken->getSeries(), $newTokenValue, new \DateTime());
            
            $response->headers->setCookie(
                $this->options['name'], 
                $this->generateCookieValue($persistentToken->getSeries(), $newTokenValue),
                $this->options['domain'],
                time() + $this->options['lifetime'],
                $this->options['path'],
                $this->options['secure'],
                $this->options['httponly']
            );
        }
        else if ($token instanceof UsernamePasswordToken) {
            $series = $this->generateRandomValue();
            $tokenValue = $this->generateRandomValue();
            
            $persistentToken = new PersistentToken((string) $token, $series, $tokenValue, new \DateTime());
            $this->tokenProvider->createNewToken($persistentToken);
            
            $response->headers->setCookie(
                $this->options['name'],
                $this->generateCookieValue($series, $tokenValue),
                $this->options['domain'],
                time() + $this->options['lifetime'],
                $this->options['path'],
                $this->options['secure'],
                $this->options['httponly']
            );
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        parent::logout($request, $response, $token);
        
        if (null !== $cookie = $request->cookies->get($this->options['name'])
            && count($parts = $this->decodeCookie($cookie)) === 2
        ) {
            list($series, $tokenValue) = $parts;
            $this->tokenProvider->deleteTokenBySeries($series);
        }
    }
    
    /**
     * Generates the value for the cookie
     * 
     * @param string $series
     * @param string $tokenValue
     * @return string
     */
    protected function generateCookieValue($series, $tokenValue)
    {
        return $this->encodeCookie(array($series, $tokenValue));
    }
    
    /**
     * Generates a cryptographically strong random value
     * 
     * @return string
     */
    protected function generateRandomValue()
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(32, $strong);
            
            if (true === $strong && false !== $bytes) {
                return base64_encode($bytes);
            }
        }
        
        if (null !== $this->logger) {
            $this->logger->warn('Could not produce a cryptographically strong random value. Please install/update the OpenSSL extension.');
        }
        
        return base64_encode(hash('sha256', uniqid(mt_rand(), true), true));
    }
}