<?php
namespace \Symfony\Component\Security\Authentication\Token;

class RememberMeToken extends Token
{
	protected $username;
	protected $data;
	
	/**
	 * Constructor
	 * @param string $username
	 * @param string $data
	 */
	public function __construct($username, $data) {
		parent::__construct();
		
		$this->username = $username;
		$this->data = $data;
	}
}