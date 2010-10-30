<?php

namespace Symfony\Component\Security\Authentication\RememberMe;

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
    
    public function __construct(UserProviderInterface $userProvider, array $options = array(), LoggerInterface $logger = null)
    {
        $this->userProvider = $userProvider;
        $this->options = $options;
        $this->logger = $logger;
    }
    
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    public function setTokenProvider(TokenProviderInterface $tokenProvider)
    {
        $this->tokenProvider = $tokenProvider;
    }
    
    public function autoLogin(Request $request)
    {
        if (null === $cookie = $request->cookies->get($this->options['name'])) {
            return;
        }
        
        if (null !== $this->logger) {
            $this->logger->debug('Remember-me cookie detected.');
        }
        
        $cookieParts = $this->decodeCookie($cookie);
        $token = $this->processAutoLoginCookie($cookieParts);
        
        if (null !== $this->logger) {
            $this->logger->debug('Remember-me cookie accepted.');
        }
        
        return $token;
    }
    
    /**
     * @param array $cookieParts
     * @return TokenInterface
     */
    abstract protected function processAutoLoginCookie($cookieParts);
    
    /**
     * 
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     * @return void
     */
    abstract protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token);
    
    /**
     * @param string $rawCookie
     * @return array
     */
    protected function decodeCookie($rawCookie)
    {
        return explode(self::COOKIE_DELIMITER, base64_decode($rawCookie));
    }
    
    protected function encodeCookie(array $cookieParts)
    {
        return base64_encode(implode(self::COOKIE_DELIMITER, $cookieParts));
    }
    
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->cancelCookie($response);
    }
    
    public function loginFail(Request $request, Response $response) 
    {
        $this->cancelCookie($response);
    }
    
    public function loginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        if (!$this->isRememberMeRequested($request)) {
            if (null !== $this->logger) {
                $this->logger->debug('Remember-me was not requested.');
            }
            
            return;
        }
        
        if (null !== $this->logger) {
            $this->logger->debug('Remember-me was requested; setting cookie.');
        }
        
        $this->onLoginSuccess($request, $response, $token);
    }
    
    protected function cancelCookie(Response $response)
    {
        $response->headers->setCookie($this->options['name'], '', null, time() - 86400);
    }
    
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