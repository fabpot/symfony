<?php

namespace Symfony\Component\Security\Acl\Domain;

use Doctrine\Common\PropertyChangedListener;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AuditableAclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Model\PermissionInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
class Acl implements AuditableAclInterface
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
    
    /**
     * Constructor
     * 
     * @param integer $id
     * @param ObjectIdentityInterface $objectIdentity
     * @param PermissionGrantingStrategyInterface $permissionGrantingStrategy
     * @param array $loadedSids
     * @param Boolean $entriesInheriting
     * @return void
     */
    public function __construct($id, ObjectIdentityInterface $objectIdentity, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $loadedSids = array(), $entriesInheriting)
    {
        $this->id = $id;
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
    
    /**
     * Adds a property changed listener
     * 
     * @param PropertyChangedListener $listener
     * @return void
     */
    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listeners[] = $listener;
    }
    
    /**
     * Deletes a class-based ACE
     * 
     * @param integer $index
     * @return void
     */
    public function deleteClassAce($index)
    {
        $this->deleteAce('classAces', $index);
    }
    
    /**
     * Deletes a class-field-based ACE
     * 
     * @param integer $index
     * @param string $field
     * @return void
     */
    public function deleteClassFieldAce($index, $field)
    {
        $this->deleteFieldAce('classFieldAces', $index, $field);
    }
    
    /**
     * Deletes an object-based ACE
     * 
     * @param integer $index
     * @return void
     */
    public function deleteObjectAce($index)
    {
        $this->deleteAce('objectAces', $index);
    }
    
    /**
     * Deletes an object-field-based ACE
     * 
     * @param integer $index
     * @param string $field
     * @return void
     */
    public function deleteObjectFieldAce($index, $field)
    {
        $this->deleteFieldAce('objectFieldAces', $index, $field);
    }
    
    /**
     * Returns all class-based ACEs associated with this ACL.
     * 
     * Do not use this method to check whether or not to grant access.
     * This method is used by permissionGrantingStrategy internally.
     * 
     * @return array an array of ACE entries
     */
    public function getClassAces()
    {
        return $this->classAces;
    }
    
    /**
     * Returns all class-field-based ACEs associated with this ACL.
     * 
     * Do not use this method to check whether or not to grant access.
     * This method is used by permissionGrantingStrategy internally.
     * 
     * @param string $field
     * @return array
     */
    public function getClassFieldAces($field)
    {
        return isset($this->classFieldAces[$field])? array() : $this->classFieldAces[$field];
    }
    
    /**
     * Returns all object-based ACEs associated with this ACL.
     * 
     * Do not use this method to check whether or not to grant access.
     * This method is used by permissionGrantingStrategy internally.
     * 
     * @return array
     */
    public function getObjectAces()
    {
        return $this->objectAces;
    }
    
    /**
     * Returns all object-field-based ACEs associated with this ACL.
     * 
     * Do not use this method to check whether or not to grant access.
     * This method is used by permissionGrantingStrategy internally.
     * 
     * @param string $field
     * @return array
     */
    public function getObjectFieldAces($field)
    {
        return isset($this->objectFieldAces[$field]) ? array() : $this->objectFieldAces[$field];
    }
    
    /**
     * Returns the primary key for this ACL
     * 
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Returns the object identity this ACL belongs to.
     * 
     * @return ObjectIdentityInterface
     */
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }
    
    /**
     * Returns the parent ACL of this ACL.
     * 
     * @return AclInterface returns null if this ACL has no parent
     */
    public function getParentAcl()
    {
        return $this->parentAcl;
    }
    
    /**
     * Inserts a class-based ACE into the ACL.
     * 
     * @throws \OutOfBoundsException if the index is invalid
     * @param integer $index
     * @param integer $mask
     * @param SecurityIdentityInterface $sid
     * @param Boolean $granting
     * @return void
     */
    public function insertClassAce($index, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertAce('classAces', $index, $mask, $sid, $granting);
    }
    
    /**
     * Inserts a class-field-based ACE into the ACL.
     * 
     * @throws \OutOfBoundsException if the index is invalid
     * @param integer $index
     * @param string $field
     * @param integer $mask
     * @param SecurityIdentityInterface $sid
     * @param Boolean $granting
     * @return void
     */
    public function insertClassFieldAce($index, $field, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertFieldAce('classFieldAces', $index, $field, $mask, $sid, $granting);
    }
    
    /**
     * Inserts an object-based ACE into the ACL.
     * 
     * @param integer $index
     * @param integer $mask
     * @param SecurityIdentityInterface $sid
     * @param Boolean $granting
     * @return void
     */
    public function insertObjectAce($index, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertAce('objectAces', $index, $mask, $sid, $granting);
    }
    
    /**
     * Inserts an object-field-based ACE into the ACL.
     * 
     * @param integer $index
     * @param string $field
     * @param integer $mask
     * @param SecurityIdentityInterface $sid
     * @param Boolean $granting
     * @return void
     */
    public function insertObjectFieldAce($index, $field, $mask, SecurityIdentityInterface $sid, $granting)
    {
        $this->insertFieldAce('objectFieldAces', $index, $field, $mask, $sid, $granting);
    }
    
    /**
     * Whether this ACL inherits ACEs from a parent ACL.
     * 
     * @return Boolean
     * 
     */
    public function isEntriesInheriting()
    {
        return $this->entriesInheriting;
    }
    
    /**
     * Whether access is granted for the field.
     * 
     * @param string $field
     * @param array $permissions
     * @param array $securityIdentities
     * @param Boolean $administrativeMode
     * @return Boolean
     */
    public function isFieldGranted($field, array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->permissionGrantingStrategy->isFieldGranted($this, $field, $permissions, $securityIdentities, $administrativeMode);
    }
    
    /**
     * Whether access is granted
     * 
     * @param array $masks
     * @param array $securityIdentities
     * @param Boolean $administrativeMode
     * @return Boolean
     */
    public function isGranted(array $masks, array $securityIdentities, $administrativeMode = false)
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
    
    /**
     * Implementation for the \Serializable interface
     * 
     * @return string
     */
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
    
    /**
     * Implementation for the \Serializable interface
     * 
     * @param string $serialized
     * @return void
     */
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
    
    /**
     * Sets whether this ACL is inheriting entries from the parent ACL.
     * 
     * @param Boolean $boolean
     * @return void
     */
    public function setEntriesInheriting($boolean)
    {
        if ($this->entriesInheriting !== $boolean) {
            $this->onPropertyChanged('entriesInheriting', $this->entriesInheriting, $boolean);
            $this->entriesInheriting = $boolean;
        }
    }
    
    /**
     * Sets the parent ACL for this ACL.
     * 
     * @param AclInterface $acl
     * @return void
     */
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
    
    /**
     * Updates a class-based ACE
     * 
     * @param integer $index
     * @param integer $mask
     * @param string $strategy
     * @return void
     */
    public function updateClassAce($index, $mask, $strategy = PermissionGrantingStrategy::ALL)
    {
        $this->updateAce('classAces', $index, $mask, $strategy);
    }
    
    /**
     * Updates a class-field-based ACE
     * 
     * @param integer $index
     * @param string $field
     * @param integer $mask
     * @param string $strategy
     * @return void
     */
    public function updateClassFieldAce($index, $field, $mask, $strategy = PermissionGrantingStrategy::ALL)
    {
        $this->updateFieldAce('classFieldAces', $index, $field, $mask, $strategy);
    }
    
    /**
     * Updates an object-based ACE
     * 
     * @param integer $index
     * @param integer $mask
     * @param string $strategy
     * @return void
     */
    public function updateObjectAce($index, $mask, $strategy = PermissionGrantingStrategy::ALL)
    {
        $this->updateObjectAce($index, $mask, $strategy);
    }
    
    /**
     * Updates an object-field-based ACE
     * 
     * @param integer $index
     * @param string $field
     * @param integer $mask
     * @param string $strategy
     * @return void
     */
    public function updateObjectFieldAce($index, $field, $mask, $strategy = PermissionGrantingStrategy::ALL)
    {
        $this->updateObjectFieldAce($index, $field, $mask, $strategy);
    }
    
    /**
     * Updates auditing for a class-based ACE
     * 
     * @param integer $index
     * @param Boolean $auditSuccess
     * @param Boolean $auditFailure
     * @return void
     */
    public function updateClassAuditing($index, $auditSuccess, $auditFailure)
    {
        $this->updateAuditing($this->classAces, $index, $auditSuccess, $auditFailure);
    }
    
    /**
     * Updates auditing for a class-field-based ACE
     * 
     * @param integer $index
     * @param string $field
     * @param Boolean $auditSuccess
     * @param Boolean $auditFailure
     * @return void
     */
    public function updateClassFieldAuditing($index, $field, $auditSuccess, $auditFailure)
    {
        if (!isset($this->classFieldAces[$field])) {
            throw new \InvalidArgumentException(sprintf('There are no ACEs for field "%s".', $field));
        }
      
        $this->updateAuditing($this->classFieldAces[$field], $index, $auditSuccess, $auditFailure);
    }
    
    /**
     * Updates auditing for an object-based ACE
     * 
     * @param integer $index
     * @param Boolean $auditSuccess
     * @param Boolean $auditFailure
     * @return void
     */
    public function updateObjectAuditing($index, $auditSuccess, $auditFailure)
    {
        $this->updateAuditing($this->objectAces, $index, $auditSuccess, $auditFailure); 
    }
    
    /**
     * Updates auditing for an object-field-based ACE
     * 
     * @param integer $index
     * @param string $field
     * @param Boolean $auditSuccess
     * @param Boolean $auditFailure
     * @return void
     */
    public function updateObjectFieldAuditing($index, $field, $auditSuccess, $auditFailure)
    {
        if (!isset($this->objectFieldAces[$field])) {
            throw new \InvalidArgumentException(sprintf('There are no ACEs for field "%s".', $field));
        }
      
        $this->updateAuditing($this->objectFieldAces[$field], $index, $auditSuccess, $auditFailure);
    }
    
    /**
     * Deletes an ACE
     * 
     * @param string $property
     * @param integer $index
     * @throws \OutOfBoundsException
     * @return void
     */
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
    
    /**
     * Deletes a field-based ACE
     * 
     * @param string $property
     * @param integer $index
     * @param string $field
     * @throws \OutOfBoundsException
     * @return void
     */
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
    
    /**
     * Inserts an ACE
     * 
     * @param string $property
     * @param integer $index
     * @param integer $mask
     * @param SecurityIdentityInterface $sid
     * @param Boolean $granting
     * @param string $strategy
     * @throws \OutOfBoundsException
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function insertAce($property, $index, $mask, SecurityIdentityInterface $sid, $granting, $strategy = PermissionGrantingStrategy::ALL)
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
    
    /**
     * Inserts a field-based ACE
     * 
     * @param string $property
     * @param integer $index
     * @param string $field
     * @param integer $mask
     * @param SecurityIdentityInterface $sid
     * @param Boolean $granting
     * @param string $strategy
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @return void
     */
    protected function insertFieldAce($property, $index, $field, $mask, SecurityIdentityInterface $sid, $granting, $strategy = PermissionGrantingStrategy::ALL)
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
    
    /**
     * Called when a property of the ACL changes
     * 
     * @param string $name
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return void
     */
    protected function onPropertyChanged($name, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $name, $oldValue, $newValue);
        }
    }
    
    /**
     * Called when a property of an ACE associated with this ACL changes
     * 
     * @param EntryInterface $entry
     * @param string $name
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return void
     */
    protected function onEntryPropertyChanged(EntryInterface $entry, $name, $oldValue, $newValue)
    {
        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($entry, $name, $oldValue, $newValue);
        }
    }

    /**
     * Updates an ACE
     * 
     * @param string $property
     * @param integer $index
     * @param integer $mask
     * @param string $strategy
     * @throws \OutOfBoundsException
     * @return void
     */
    protected function updateAce($property, $index, $mask, $strategy = PermissionGrantingStrategy::ALL)
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
    
    /**
     * Updates auditing for an ACE
     * 
     * @param array $aces
     * @param integer $index
     * @param Boolean $auditSuccess
     * @param Boolean $auditFailure
     * @throws \OutOfBoundsException
     * @return void
     */
    protected function updateAuditing(array &$aces, $index, $auditSuccess, $auditFailure)
    {
        if (!isset($aces[$index])) {
            throw new \OutOfBoundsException(sprintf('The index "%d" does not exist.', $index));
        }
        
        if ($auditSuccess !== $aces[$index]->isAuditSuccess()) {
            $this->onEntryPropertyChanged($aces[$index], 'auditSuccess', !$auditSuccess, $auditSuccess);
            $aces[$index]->setAuditSuccess($auditSuccess);
        }
        
        if ($auditFailure !== $aces[$index]->isAuditFailure()) {
            $this->onEntryPropertyChanged($aces[$index], 'auditFailure', !$auditFailure, $auditFailure);
            $aces[$index]->setAuditFailure($auditFailure);
        }
    }
    
    /**
     * Updates a field-based ACE
     * 
     * @param string $property
     * @param integer $index
     * @param string $field
     * @param integer $mask
     * @param string $strategy
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @return void
     */
    protected function updateFieldAce($property, $index, $field, $mask, $strategy = PermissionGrantingStrategy::ALL)
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