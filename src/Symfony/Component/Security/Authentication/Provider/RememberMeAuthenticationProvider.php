<?php
namespace Symfony\Component\Security\Authentication\Provider;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

class RememberMeAuthenticationProvider implements AuthenticationProviderInterface
{
    protected $key;
    
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    public function getKey()
    {
        return $this->key;
    }
    
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }
        
        if ($this->key !== $token->getKey()) {
            throw new BadCredentialsException('The presented key does not match.');
        }
        
        return $token;
    }
    
    public function supports(TokenInterface $token)
    {
        return $token instanceof RememberMeToken;
    }
}