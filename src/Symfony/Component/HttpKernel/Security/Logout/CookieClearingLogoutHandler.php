<?php
namespace \Symfony\Component\HttpKernel\Security\Logout;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CookieClearingLogoutHandler implements LogoutHandlerInterface
{
    protected $cookieNames;
    
    public function __construct(array $cookieNames)
    {
        $this->cookieNames = $cookieNames;
    }
    
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $expires = time() - 86400;
        
        foreach ($this->cookieNames as $cookieName) {
            $response->headers->setCookie($cookieName, '', null, $expires);
        }
    }
}