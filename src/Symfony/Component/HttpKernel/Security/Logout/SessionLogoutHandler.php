<?php
namespace Symfony\Component\HttpKernel\Security\Logout;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SessionLogoutHandler implements LogoutHandlerInterface
{
	public function logout(Request $request, Response $response, TokenInterface $token)
	{
        $request->getSession()->invalidate();
	}	
}