<?php
namespace \Symfony\Component\Security\Authentication\Token;

/**
 * Base class for "Remember Me" tokens
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberMeToken extends Token
{
	/**
	 * Constructor
	 * @param string $username
	 * @param string $data
	 */
	public function __construct(AccountInterface $user) {
		parent::__construct($user->getRoles());
		
		$this->user = $user;
	}
}