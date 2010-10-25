<?php
use Symfony\Component\Security\User\UserProviderInterface;
namespace \Symfony\Component\Security\Authentication\Provider;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

class SimpleHashRememberMeAuthenticationProvider implements AuthenticationProviderInterface
{
	protected $userProvider;
	
	public function __construct(UserProviderInterface $userProvider) {
		$this->userProvider = $userProvider;
	}
	
	public function authenticate(TokenInterface $token)
	{
		if (!$this->supports($token)) {
			return null;
		}
		
		$user = $this->userProvider->loadUserByUsername($token->getUsername());
		if ($token->getHash() !== $token->generateHash($user->getPassword(), $user->getSalt())) {
			throw new AuthenticationException('Invalid remember me token provided');
		}
		
		
	}
	
	public function supports(TokenInterface $token)
	{
		return $token instanceof SimpleHashRememberMeToken;
	}
}