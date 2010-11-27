<?php

namespace Symfony\Component\Security\Acl\Voter;

use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Authorization\Voter\VoterInterface;

/**
 * This voter can be used as a base class for implementing your own permissions.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Voter implements VoterInterface
{
    protected $aclProvider;
    protected $roleHierarchy;
    protected $processMap;
    
    public function __construct(AclProviderInterface $aclProvider, RoleHierarchyInterface $roleHierarchy, array $processMap)
    {
        $this->aclProvider = $aclProvider;
        $this->roleHierarchy = $roleHierarchy;
        $this->processMap = $processMap;
    }
    
    public function supportsAttribute($attribute)
    {
        return isset($this->processMap[$attribute]);
    }
    
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (null === $object) {
            return self::ACCESS_ABSTAIN;
        }
        else if ($object instanceof FieldVote) {
            $field = $object->getField();
            $object = $object->getDomainObject();
        }
        else {
            $field = null;
        }
        
        if (null === $oid = $this->retrieveObjectIdentity($object)) {
            return self::ACCESS_ABSTAIN;
        } 
        $sids = $this->retrieveSecurityIdentities($token);
        
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }
            
            try {
                $acl = $this->aclProvider->findAcl($oid, $sids);
            }
            catch (AclNotFoundException $noAcl) {
                return self::ACCESS_DENIED;
            }
            
            try {
                if (null === $field && $acl->isGranted($this->processMap[$attribute], $sids, false)) {
                    return self::ACCESS_GRANTED;    
                }
                else if (null !== $field && $acl->isFieldGranted($field, $this->processMap[$attribute], $sids, false)) {
                    return self::ACCESS_GRANTED;
                }
                else {
                    return self::ACCESS_DENIED;
                }
            }
            catch (NoAceFoundException $noAce) {
                return self::ACCESS_DENIED;
            }
        }
        
        return self::ACCESS_ABSTAIN;
    }
    
    /**
     * Retrieves an object identity from the domain object
     * 
     * @param object $object
     * @return ObjectIdentityInterface
     */
    protected function retrieveObjectIdentity($object)
    {
        try {
            return ObjectIdentity::from($object);
        } 
        catch (\InvalidArgumentException $invalidObject) {
            return null;    
        }
    }
    
    /**
     * Retrieves all security identities available to the authenticated user
     * 
     * @param TokenInterface $token
     * @return array
     */
    protected function retrieveSecurityIdentities(TokenInterface $token)
    {
        $sids = array();
        $sids[] = UserSecurityIdentity::fromToken($token);
        
        foreach ($this->roleHierarchy->getReachableRoles($token->getRoles()) as $role) {
            $sids[] = new RoleSecurityIdentity($role);
        }
        
        return $sids;
    }
    
    /**
     * You can override this method when writing a voter for a specific domain
     * class.
     * 
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return true;
    }
}