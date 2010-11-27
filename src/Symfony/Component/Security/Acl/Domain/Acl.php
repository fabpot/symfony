<?php

namespace Symfony\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Model\EntryInterface;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Symfony\Component\Security\Acl\Model\AuditableAclInterface;
use Symfony\Component\Security\Acl\Model\PermissionInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;

/**
 * An ACL implementation.
 * 
 * Each object identity has exactly one associated ACL. Each ACL can have four
 * different types of ACEs (class ACEs, object ACEs, class field ACEs, object field 
 * ACEs). 
 * 
 * You should not iterate over the ACEs yourself, but instead use isGranted(),
 * or isFieldGranted(). These will utilize an implementation of PermissionGrantingStrategy
 * internally.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Acl implements AclInterface, MutableAclInterface, AuditableAclInterface, NotifyPropertyChanged, \Serializable
{
    protected $parentAcl;
    protected $permissionGrantingStrategy;
    protected $objectIdentity;
    protected $classAces;
    protected $classFieldAces;
    protected $objectAces;
    protected $objectFieldAces;
    protected $id;
    protected $loadedSids;
    protected $entriesInheriting;
    protected $listeners;
    
    public function __construct(ObjectIdentityInterface $objectIdentity, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $loadedSids = array(), $entriesInheriting)
    {
        $this->objectIdentity = $objectIdentity;
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
        $this->loadedSids = $loadedSids;
        $this->entriesInheriting = $entriesInheriting;
        $this->parentAcl = null;
        $this->classAces = array();
        $this->classFieldAces = array();
        $this->objectAces = array();
        $this->objectFieldAces = array();
        $this->listeners = array();
    }
    
    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listeners[] = $listener;
    }
    
    public function deleteClassAce($index)
    {
        $this->deleteAce('classAces', $index);
    }
    
    public function deleteClassFieldAce($index, $field)
    {
        $this->deleteFieldAce('classFieldAces', $index, $field);
    }
    
    public function deleteObjectAce($index)
    {
        $this->deleteAce('objectAces', $index);
    }
    
    public function deleteObjectFieldAce($index, $field)
    {
        $this->deleteFieldAce('objectFieldAces', $index, $field);
    }
    
    /**
     * Do not use this method to check whether or not to grant access.
     * 
     * This method is used by permissionGrantingStrategy internally.
     * 
     * @return array an array of ACE entries
     */
    public function getClassAces()
    {
        return $this->classAces;
    }
    
    public function getClassFieldAces($field)
    {
        return isset($this->classFieldAces[$field])? array() : $this->classFieldAces[$field];
    }
    
    public function getObjectAces()
    {
        return $this->objectAces;
    }
    
    public function getObjectFieldAces($field)
    {
        return isset($this->objectFieldAces[$field]) ? array() : $this->objectFieldAces[$field];
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }
    
    public function getParentAcl()
    {
        return $this->parentAcl;
    }
    
    public function getClassFieldAcl()
    {
        return $this->classFieldAcl;
    }
    
    public function getObjectFieldAcl()
    {
        return $this->objectFieldAcl;
    }
    
    public function getClassAcl()
    {
        return $this->classAcl;
    }
    
    public function insertClassAce($index, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertAce('classAces', $index, $mask, $sid, $granting);
    }
    
    public function insertClassFieldAce($index, $field, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertFieldAce('classFieldAces', $index, $field, $mask, $sid, $granting);
    }
    
    public function insertObjectAce($index, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertAce('objectAces', $index, $mask, $sid, $granting);
    }
    
    public function insertObjectFieldAce($index, $field, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertFieldAce('objectFieldAces', $index, $field, $mask, $sid, $granting);
    }
    
    public function isEntriesInheriting()
    {
        return $this->entriesInheriting;
    }
    
    public function isFieldGranted($field, $permissions, $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isFieldGranted($this, $field, $permissions, $securityIdentities, $administrativeMode);
    }
    
    public function isGranted($permissions, $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isGranted($this, $permissions, $securityIdentities, $administrativeMode);
    }
    
    /**
     * {@inheritDoc}
     */
    public function isSidLoaded($sids)
    {
        if (0 === count($this->loadedSids)) {
            return true;
        }
        
        foreach ((array) $sids as $sid) {
            $found = false;
            
            foreach ($this->loadedSids as $loadedSid) {
                if ($loadedSid->equals($sid)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                return false;
            }
        }
        
        return true;
    }
    
    public function serialize()
    {
        return serialize(array(
            null === $this->parentAcl ? null : $this->parentAcl->getId(),
            $this->objectIdentity,
            $this->classAces,
            $this->classFieldAces,
            $this->objectAces,
            $this->objectFieldAces,
            $this->id,
            $this->loadedSids,
            $this->entriesInheriting,
        ));
    }
    
    public function unserialize($serialized) 
    {
        list($this->parentAcl, 
             $this->objectIdentity, 
             $this->classAces, 
             $this->classFieldAces, 
             $this->objectAces,
             $this->objectFieldAces,
             $this->id,
             $this->loadedSids,
             $this->entriesInheriting
        ) = unserialize($serialized);
        
        $this->listeners = array();
    }
    
    public function setEntriesInheriting($boolean)
    {
        $boolean = !!$boolean;
        
        if ($this->entriesInheriting !== $boolean) {
            $this->onPropertyChanged('entriesInheriting', $this->entriesInheriting, $boolean);
            $this->entriesInheriting = $boolean;
        }
    }
    
    public function setParentAcl(AclInterface $acl)
    {
        if (null !== $acl->getId()) {
            throw new \InvalidArgumentException('$acl must have an ID.');
        }
        
        if ($this->parentAcl !== $acl) {
            $this->onPropertyChanged('parentAcl', $this->parentAcl, $acl);
            $this->parentAcl = $acl;
        }
    }
    
    public function updateClassAce($index, $mask, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        $this->updateAce('classAces', $index, $mask, $strategy);
    }
    
    public function updateClassFieldAce($index, $field, $mask, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        $this->updateFieldAce('classFieldAces', $index, $field, $mask, $strategy);
    }
    
    public function updateObjectAce($index, $mask, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        $this->updateObjectAce($index, $mask, $strategy);
    }
    
    public function updateObjectFieldAce($index, $field, $mask, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        $this->updateObjectFieldAce($index, $field, $mask, $strategy);
    }
    
    public function updateAuditing($index, $auditSuccess, $auditFailure)
    {
        if (!isset($this->aces[$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }
        
        $this->aces[$index]->setAuditFailure($auditFailure);
        $this->aces[$index]->setAuditSuccess($auditSuccess);
    }
    
    protected function deleteAce($property, $index)
    {
        $aces =& $this->$property;
        if (!isset($aces[$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }
        
        $oldValue = $this->$property;
        unset($aces[$index]);
        $this->$property = array_values($this->$property);
        $this->onPropertyChanged($property, $oldValue, $this->$property);

        for ($i=$index,$c=count($this->$property); $i<$c; $i++) {
            $this->onEntryPropertyChanged($aces[$i], 'aceOrder', $i+1, $i);
        }
    }
    
    protected function deleteFieldAce($property, $index, $field)
    {
        $aces =& $this->$property;
        if (!isset($aces[$field][$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }
        
        $oldValue = $this->$property;
        unset($aces[$field][$index]);
        $aces[$field] = array_values($aces[$field]);
        $this->onPropertyChanged($property, $oldValue, $this->$property);
        
        for ($i=$index,$c=count($aces[$field]); $i<$c; $i++) {
            $this->onEntryPropertyChanged($aces[$field][$i], 'aceOrder', $i+1, $i);
        }
    }
    
    protected function insertAce($property, $index, $mask, SecurityIdentityInterface $sid, $granting, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        if ($index < 0 || $index > count($this->$property)) {
            throw new \OutOfBoundsException(sprintf('The index must be in the interval [0, %d].', count($this->$property)));
        }

        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }
        
        $aces =& $this->$property;
        $oldValue = $this->$property;
        if (isset($aces[$index])) {
            $this->$property = array_merge(
                array_slice($this->$property, 0, $index),
                array(true),
                array_slice($this->$property, $index)
            );
            
            for ($i=$index,$c=count($this->$property)-1; $i<$c; $i++) {
                $this->onEntryPropertyChanged($aces[$i+1], 'aceOrder', $i, $i+1);
            }
        }
        
        $aces[$index] = new Entry(null, $this, $sid, $strategy, $mask, $granting, false, false);
        $this->onPropertyChanged($property, $oldValue, $this->$property);
    }
    
    protected function insertFieldAce($property, $index, $field, $mask, SecurityIdentityInterface $sid, $granting, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        if (0 === strlen($field)) {
            throw new \InvalidArgumentException('$field cannot be empty.');
        }
        
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }
        
        $aces =& $this->$property;
        if (!isset($aces[$field])) {
            $aces[$field] = array();
        }
        
        if ($index < 0 || $index > count($aces[$field])) {
            throw new \OutOfBoundsException(sprintf('The index must be in the interval [0, %d].', count($this->$property)));
        }
        
        $oldValue = $aces;
        if (isset($aces[$field][$index])) {
            $aces[$field] = array_merge(
                array_slice($aces[$field], 0, $index),
                array(true),
                array_slice($aces[$field], $index)
            );

            for ($i=$index,$c=count($aces[$field])-1; $i<$c; $i++) {
                $this->onEntryPropertyChanged($aces[$field][$i+1], 'aceOrder', $i, $i+1);
            }
        }
        
        $aces[$field][$index] = new FieldEntry(null, $this, $field, $sid, $strategy, $mask, $granting, false, false);
        $this->onPropertyChanged($property, $oldValue, $this->$property);
    }
    
    protected function onPropertyChanged($name, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $name, $oldValue, $newValue);
        }
    }
    
    protected function onEntryPropertyChanged(EntryInterface $entry, $name, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($entry, $name, $oldValue, $newValue);
        }
    }

    protected function updateAce($property, $index, $mask, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        $aces =& $this->$property;
        if (!isset($aces[$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }
        
        $ace = $aces[$index];
        if ($mask !== $oldMask = $ace->getMask()) {
            $this->onEntryPropertyChanged($ace, 'mask', $oldMask, $mask);
            $ace->setMask($mask);
        }
        if ($strategy !== $oldStrategy = $ace->getStrategy()) {
            $this->onEntryPropertyChanged($ace, 'strategy', $oldStrategy, $strategy);
            $ace->setStrategy($strategy);
        }
    }
    
    protected function updateFieldAce($property, $index, $field, $mask, $strategy = PermissionGrantingStrategy::EQUAL)
    {
        if (0 === strlen($field)) {
            throw new \InvalidArgumentException('$field cannot be empty.');
        }
        
        $aces =& $this->$property;
        if (!isset($aces[$field][$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }
        
        $ace = $aces[$field][$index];
        if ($mask !== $oldMask = $ace->getMask()) {
            $this->onEntryPropertyChanged($ace, 'mask', $oldMask, $mask);
            $ace->setMask($mask);
        }
        if ($strategy !== $oldStrategy = $ace->getStrategy()) {
            $this->onEntryPropertyChanged($ace, 'strategy', $oldStrategy, $strategy);
            $ace->setStrategy($strategy);
        }
    }
}