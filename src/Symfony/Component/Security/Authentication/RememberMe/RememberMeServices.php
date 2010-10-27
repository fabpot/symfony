<?php

use Symfony\Component\Security\User\UserProviderInterface;
use Symfony\Component\Security\Authentication\AuthenticationManagerInterface;
namespace \Symfony\Component\Security\Authentication\RememberMe;

abstract class RememberMeServices implements RememberMeServicesInterface
{
	protected $authenticationManager;
	protected $userProvider;
	protected $options;
	protected $tokenProvider;
	
	public function __construct(AuthenticationManagerInterface $authenticationManager, UserProviderInterface $userProvider, array $options = array())
	{
		$this->authenticationManager = $authenticationManager;
		$this->userProvider = $userProvider;
		$this->options = $options;
	}
	
	public function setTokenProvider(TokenProviderInterface $tokenProvider)
	{
		$this->tokenProvider = $tokenProvider;
	}
	
	abstract public function autoLogin(Request $request);
	
	public function loginFail() 
	{
		// TODO
	}
	
	public function loginSuccess()
	{
		// TODO
	}
}