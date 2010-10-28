<?php
namespace \Symfony\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

interface RememberMeServicesInterface
{
	/**
	 * This method will be called whenever the SecurityContextHolder does not 
	 * contain an Authentication object and Spring Security wishes to provide 
	 * an implementation with an opportunity to authenticate the request using 
	 * remember-me capabilities. Spring Security makes no attempt whatsoever 
	 * to determine whether the browser has requested remember-me services or 
	 * presented a valid cookie. Such determinations are left to the implementation. 
	 * If a browser has presented an unauthorised cookie for whatever reason, 
	 * it should be silently ignored and invalidated using the 
	 * HttpServletResponse object. 
	 * 
	 * @param Request $request
	 * @return TokenInterface
	 */
	function autoLogin(Request $request);
	
	/**
	 * Called whenever an interactive authentication attempt was made, but the credentials supplied by the user were missing or otherwise invalid.
	 */
	function onLoginFail();

	/**
	 * Called whenever an interactive authentication attempt is successful. An implementation may automatically set a remember-me token in the HttpServletResponse, although this is not recommended. Instead, implementations should typically look for a request parameter that indicates the browser has presented an explicit request for authentication to be remembered, such as the presence of a HTTP POST parameter. 
	 */
	function onLoginSuccess(Request $request, Response $response, TokenInterface $token);
}