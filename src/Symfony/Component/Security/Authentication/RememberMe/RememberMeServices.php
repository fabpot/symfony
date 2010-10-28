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
		// TODO: Invalidate any and all remember-me tokens
	}
	
	public function loginSuccess()
	{
		// TODO: Set the remember-me token if requested (this should be called after
		//       an interactive authentication was successful; e.g. after the form-login)
	}
}