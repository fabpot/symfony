<?php
namespace Symfony\Component\Security\Authentication\Provider;

use Symfony\Component\Security\User\AccountInterface;

use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Authentication\Token\RememberMeToken;

class RememberMeAuthenticationProvider implements AuthenticationProviderInterface
{
    protected $accountChecker;
    protected $key;
    
    public function __construct(AccountCheckerInterface $accountChecker, $key)
    {
        $this->accountChecker = $accountChecker;
        $this->key = $key;
    }
    
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }
        
        if ($this->key !== $token->getKey()) {
            throw new BadCredentialsException('The presented key does not match.');
        }
        
        if (null !== $user = $token->getUser()) {
            $this->accountChecker->checkPreAuth($user);
            $this->accountChecker->checkPostAuth($user);
        }
            
        return $token;
    }
    
    public function supports(TokenInterface $token)
    {
        return $token instanceof RememberMeToken;
    }
}