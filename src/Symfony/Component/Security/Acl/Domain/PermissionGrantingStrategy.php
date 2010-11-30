<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionInterface;
use Symfony\Component\Security\Acl\Model\AuditLoggerInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * The permission granting strategy to apply to the access control list.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PermissionGrantingStrategy implements PermissionGrantingStrategyInterface
{
    const EQUAL = 'equal';
    const ALL   = 'all';
    const ANY   = 'any';
    
    protected $auditLogger;
    
    public function setAuditLogger(AuditLoggerInterface $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }
    
    public function getAuditLogger()
    {
        return $this->auditLogger;
    }
    
    public function isGranted(AclInterface $acl, $permissions, $sids, $administrativeMode = false)
    {
        try {
            try {
                $aces = $acl->getObjectAces();
                
                if (0 === count($aces)) {
                    throw new NoAceFoundException('No applicable ACE was found.');
                }
                
                return $this->hasSufficientPermissions($acl, $aces, $permissions, $sids, $administrativeMode);
            }
            catch (NoAceFoundException $noObjectAce) {
                $aces = $acl->getClassAces();
                
                if (0 === count($aces)) {
                    throw new NoAceFoundException('No applicable ACE was found.');
                }
                
                return $this->hasSufficientPermissions($acl, $acl->getClassAces(), $permissions, $sids, $administrativeMode);
            }
        }
        catch (NoAceFoundException $noClassAce) {
            if ($acl->isEntriesInheriting() && null !== $parentAcl = $acl->getParentAcl()) {
                return $parentAcl->isGranted($permissions, $sids, $administrativeMode);
            }

            throw new NoAceFoundException('No applicable ACE was found.');
        }
    }
    
    public function isFieldGranted(AclInterface $acl, $field, $permissions, $sids, $administrativeMode = false)
    {
        try {
            try {
                $aces = $acl->getObjectFieldAces($field);
                if (0 === count($aces)) {
                    throw new NoAceFoundException('No applicable ACE was found.');
                }
                
                return $this->hasSufficientPermissions($acl, $aces, $permissions, $sids, $administrativeMode);
            }
            catch (NoAceFoundException $noObjectAces) {
                $aces = $acl->getClassFieldAces($field);
                if (0 === count($aces)) {
                    throw new NoAceFoundException('No applicable ACE was found.');
                }
                
                return $this->hasSufficientPermissions($acl, $aces, $permissions, $sids, $administrativeMode);
            }
        } catch (NoAceFoundException $noClassAces) {
            if ($acl->isEntriesInheriting() && null !== $parentAcl = $acl->getParentAcl()) {
                return $parentAcl->isFieldGranted($field, $permissions, $sids, $administrativeMode);
            }

            throw new NoAceFoundException('No applicable ACE was found.');
        }
    }
    
    /**
     * Makes an authorization decision.
     * 
     * The order of ACEs, and SIDs is significant; the order of permission masks
     * not so much. It is important to note that the more specific security
     * identities should be at the beginning of the SIDs array in order for this
     * strategy to produce intuitive authorization decisions.
     * 
     * First, we will iterate over permissions, then over security identities.
     * For each combination of permission, and identity we will test the
     * available ACEs until we find one which is applicable.
     * 
     * The first applicable ACE will make the ultimate decision for the
     * permission/identity combination. If it is granting, this method will return
     * true, if it is denying, the method will continue to check the next
     * permission/identity combination.
     * 
     * This process is repeated until either a granting ACE is found, or no
     * permission/identity combinations are left. In the latter case, we will
     * call this method on the parent ACL if it exists, and isEntriesInheriting
     * is true. Otherwise, we will either throw an NoAceFoundException, or deny
     * access finally.
     * 
     * @param AclInterface $acl
     * @param array $aces an array of ACE to check against
     * @param array $masks an array of permission masks
     * @param array $sids an array of SecurityIdentityInterface implementations
     * @param Boolean $administrativeMode true turns off audit logging
     * @return Boolean true, or false; either granting, or denying access respectively.
     */
    protected function hasSufficientPermissions(AclInterface $acl, array &$aces, array &$masks, array &$sids, $administrativeMode)
    {
        $firstRejectedAce  = null;
        
        foreach ($masks as $requiredMask) {
            foreach ($sids as $sid) {
                foreach ($aces as $ace) {
                    if ($this->isAceApplicable($requiredMask, $sid, $ace)) {
                        if ($ace->isGranting()) {
                            if (!$administrativeMode && null !== $this->auditLogger) {
                                $this->auditLogger->logIfNeeded(true, $ace);
                            }
                            
                            return true;
                        }
                        
                        if (null === $firstRejectedAce) {
                            $firstRejectedAce = $ace;
                        }
                        
                        break 2;
                    }
                }
            }
        }

        if (null !== $firstRejectedAce) {
            if (!$administrativeMode && null !== $this->auditLogger) {
                $this->auditLogger->logIfNeeded(false, $firstRejectedAce);
            }
            
            return false;
        }

        throw new NoAceFoundException('No applicable ACE was found.');
    }
    
    /**
     * Determines whether the ACE is applicable to the given permission/security
     * identity combination. 
     * 
     * Per default, we support three different comparison strategies.
     * 
     * Strategy ALL:
     * The ACE will be considered applicable when all the turned-on bits in the
     * required mask are also turned-on in the ACE mask.
     * 
     * Strategy ANY:
     * The ACE will be considered applicable when any of the turned-on bits in 
     * the required mask is also turned-on the in the ACE mask.
     * 
     * Strategy EQUAL:
     * The ACE will be considered applicable when the bitmasks are equal.
     * 
     * @param SecurityIdentityInterface $sid
     * @param EntryInterface $ace
     * @param int $requiredMask
     * @return Boolean
     */
    protected function isAceApplicable($requiredMask, SecurityIdentityInterface $sid, EntryInterface $ace)
    {
        if (false === $ace->getSecurityIdentity()->equals($sid)) {
            return false;
        }
        
        $strategy = $ace->getStrategy();
        if (self::ALL === $strategy) {
            return $requiredMask === ($ace->getMask() & $requiredMask);
        }
        else if (self::ANY === $strategy) {
            return 0 !== ($ace->getMask() & $requiredMask);
        }
        else if (self::EQUAL === $strategy) {
            return $requiredMask === $ace->getMask();
        }
        else {
            throw new \RuntimeException(sprintf('The strategy "%s" is not supported.', $strategy));
        }
    }
}