<?php
namespace \Symfony\Component\Security\Authentication\RememberMe;

interface TokenProviderInterface
{
	/**
	 * Loads the active token for the given series
	 * @param string $series
	 * @return PersistentTokenInterface
	 */
	function loadTokenBySeries($series);
	
	/**
	 * Deletes all tokens belonging to username
	 * @param string $series
	 */
	function deleteTokensBySeries($series);
	
	/**
	 * Updates the token according to this data
	 * 
	 * @param string $series
	 * @param string $tokenValue
	 * @param Date $lastUsed
	 */
	function updateToken($series, $tokenValue, $lastUsed);
	
	/**
	 * Creates a new token
	 * @param PersistentTokenInterface $token
	 */
	function createNewToken($token);
}