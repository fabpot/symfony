<?php
namespace \Symfony\Component\Security\Authentication\Token;

/**
 * Base class for "Remember Me" tokens
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class RememberMeToken extends Token
{
	protected $username;
	
	/**
	 * Constructor
	 * @param string $username
	 * @param string $data
	 */
	public function __construct($username, $data) {
		parent::__construct();
		
		$this->username = $username;
		$this->extractData($data);
	}
	
	public function getUsername() 
	{
		return $this->username;
	}
	
	abstract protected function extractData($data);
}