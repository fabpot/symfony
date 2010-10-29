<?php
namespace Symfony\Component\Security\Authentication\RememberMe;

/**
 * This class is only used by PersistentTokenRememberMeServices internally.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PersistentToken implements PersistentTokenInterface
{
	private $username;
	private $series;
	private $tokenValue;
	private $lastUsed;
	
	public function __construct($username, $series, $tokenValue, \DateTime $lastUsed)
	{
		$this->username = $username;
		$this->series = $series;
		$this->tokenValue = $tokenValue;
		$this->lastUsed = $lastUsed;
	}
	
	public function getUsername()
	{
		return $this->username;
	}
	
	public function getSeries()
	{
		return $this->series;
	}
	
	public function getTokenValue()
	{
		return $this->tokenValue;
	}
	
	public function getLastUsed()
	{
		return $this->lastUsed;
	}	
}