<?php
namespace \Symfony\Component\HttpKernel\Security\Logout;

class SessionLogoutHandler implements LogoutHandlerInterface
{
	public function logout($request, $response, $token)
	{
        $request->getSession()->invalidate();
	}	
}