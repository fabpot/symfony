<?php

namespace Symfony\Component\HttpKernel\Security\RememberMe;

use Symfony\Component\Security\Authentication\Token\RememberMeToken;
use Symfony\Component\HttpKernel\Security\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\User\UserProviderInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class implementing the RememberMeServicesInterface
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class RememberMeServices implements RememberMeServicesInterface, LogoutHandlerInterface
{
    const COOKIE_DELIMITER = ':';
    
    protected $userProvider;
    protected $options;
    protected $logger;
    protected $tokenProvider;
    protected $key;
    
    /**
     * Constructor
     * 
     * @param UserProviderInterface $userProvider
     * @param array $options
     * @param LoggerInterface $logger
     */
    public function __construct(UserProviderInterface $userProvider, array $options = array(), LoggerInterface $logger = null)
    {
        $this->userProvider = $userProvider;
        $this->options = $options;
        $this->logger = $logger;
    }
    
    /**
     * Sets the private remember-me key
     * 
     * @param string $key
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    /**
     * Sets the token provider
     * 
     * @param TokenProviderInterface $tokenProvider
     * @return void
     */
    public function setTokenProvider(TokenProviderInterface $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }
    
    /**
     * Implementation of RememberMeServicesInterface. Detects whether a remember-me
     * cookie was set, decodes it, and hands it to subclasses for further processing.
     * 
     * @param Request $request
     * @return TokenInterface
     */
    public function autoLogin(Request $request)
    {
        if (null === $cookie = $request->cookies->get($this->options['name'])) {
            return;
        }
        
        if (null !== $this->logger) {
            $this->logger->debug('Remember-me cookie detected.');
        }
        
        $cookieParts = $this->decodeCookie($cookie);
        $token = $this->processAutoLoginCookie($cookieParts, $request);
        
        if (!$token instanceof TokenInterface) {
            throw new \RuntimeException('processAutoLoginCookie() must return a TokenInterface implementation.');
        }
        
        if (null !== $this->logger) {
            $this->logger->debug('Remember-me cookie accepted.');
        }
        
        return $token;
    }
    
    /**
     * Subclasses should validate the cookie and do any additional processing
     * that is required. This is called from autoLogin().
     * 
     * @param array $cookieParts
     * @param Request $request
     * @return TokenInterface
     */
    abstract protected function processAutoLoginCookie(array $cookieParts, Request $request);
    
    /**
     * This is called after a user has been logged in successfully, and has
     * requested remember-me capabilities. The implementation usually sets a
     * cookie and possibly stores a persistent record of it.
     * 
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     * @return void
     */
    abstract protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token);
    
    /**
     * Decodes the raw cookie value
     * 
     * @param string $rawCookie
     * @return array
     */
    protected function decodeCookie($rawCookie)
    {
        return explode(self::COOKIE_DELIMITER, base64_decode($rawCookie));
    }
    
    /**
     * Encodes the cookie parts
     * 
     * @param array $cookieParts
     * @return string
     */
    protected function encodeCookie(array $cookieParts)
    {
        return base64_encode(implode(self::COOKIE_DELIMITER, $cookieParts));
    }
    
    /**
     * Implementation for LogoutHandlerInterface. Deletes the cookie.
     * 
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     * @return void
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->cancelCookie($response);
    }
    
    /**
     * Implementation for RememberMeServicesInterface. Deletes the cookie when
     * an attempted authentication fails.
     * 
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function loginFail(Request $request, Response $response) 
    {
        $this->cancelCookie($response);
    }
    
    /**
     * Implementation for RememberMeServicesInterface. This is called when an
     * authentication is successful.  
     * 
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token The token that resulted in a successful authentication
     * @return void
     */
    public function loginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        if (!$token instanceof RememberMeToken) {
            if (!$this->isRememberMeRequested($request)) {
                if (null !== $this->logger) {
                    $this->logger->debug('Remember-me was not requested.');
                }
                
                return;
            }
            
            if (null !== $this->logger) {
                $this->logger->debug('Remember-me was requested; setting cookie.');
            }
        }
        else if (null !== $this->logger) {
            $this->logger->debug('Re-newing remember-me token; setting cookie.');
        }
        
        $this->onLoginSuccess($request, $response, $token);
    }
    
    /**
     * Deletes the remember-me cookie
     *  
     * @param Response $response
     * @return void
     */
    protected function cancelCookie(Response $response)
    {
        $response->headers->setCookie($this->options['name'], '', null, time() - 86400);
    }
    
    /**
     * Checks whether remember-me capabilities where requested
     * 
     * @param Request $request
     * @return Boolean
     */
    protected function isRememberMeRequested(Request $request)
    {
        if (true === $this->options['always_remember_me']) {
            return true;
        }
        
        $parameter = $request->request->get($this->options['remember_me_parameter']);
        
        if ($parameter === null && null !== $this->logger) {
            $this->logger->debug(sprintf('Did not send remember-me cookie (remember-me parameter "%s" was not sent).', $this->options['remember_me_parameter']));
        }
        
        return $parameter === 'true' || $parameter === 'on' || $parameter === '1' || $parameter === 'yes';
    }
}