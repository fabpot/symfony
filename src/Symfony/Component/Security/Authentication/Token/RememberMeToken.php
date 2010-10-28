<?php
namespace \Symfony\Component\Security\Authentication\Token;

/**
 * Base class for "Remember Me" tokens
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberMeToken extends Token
{
	protected $key;
	
	/**
	 * Constructor
	 * @param string $username
	 * @param string $key
	 */
	public function __construct(AccountInterface $user, $key) {
		parent::__construct($user->getRoles());
		
		$this->user = $user;
		$this->key = $key;
	}
	
	public function getKey()
	{
		return $this->key;
	}
}