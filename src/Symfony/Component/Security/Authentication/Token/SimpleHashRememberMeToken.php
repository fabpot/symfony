<?php
namespace \Symfony\Component\Security\Authentication\Token;

class SimpleHashRememberMeToken extends RememberMeToken
{
	protected $expires;
	protected $hash;
	
	public function getExpires()
	{
		return $this->expires;
	}
	
	public function getHash()
	{
		return $this->hash;
	}
	
	public function generateHash($password, $salt) {
		return hash('sha256', sprintf('%s:%d:%s:%s', $this->getUsername(), $this->getExpires(), $password, $salt));
	}
	
	protected function extractData($data) 
	{
		list($this->expires, $this->hash) = explode(':', $data);
	}
}