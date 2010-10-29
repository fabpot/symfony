<?php
namespace Symfony\Component\Security\Authentication\RememberMe;

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
    protected function processAutoLoginCookie($cookieParts)
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
    
    protected function generateCookieValue($series, $tokenValue)
    {
        return $this->encodeCookie(array($series, $tokenValue));
    }
    
    protected function generateRandomValue()
    {
        return base_convert(hash('sha256', uniqid(mt_rand(), true)), 16, 36);
    }
}