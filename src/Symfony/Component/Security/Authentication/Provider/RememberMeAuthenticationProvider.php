<?php
namespace \Symfony\Component\Security\Authentication\Provider;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

class RememberMeAuthenticationProvider implements AuthenticationProviderInterface
{
	public function authenticate(TokenInterface $token)
	{
		if (!$this->supports($token)) {
			return null;
		}
		
		
	}
	
	public function supports(TokenInterface $token)
	{
		return $token instanceof RememberMeToken;
	}
}