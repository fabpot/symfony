<?php

namespace Symfony\Component\Security\Acl\Dbal;

use Symfony\Component\Security\Acl\Model\FieldEntryInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\Common\PropertyChangedListener;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class MutableAclProvider extends AclProvider implements MutableAclProviderInterface, PropertyChangedListener
{
    protected $propertyChanges;
    
    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $options, AclCacheInterface $aclCache = null)
    {
        parent::__construct($connection, $permissionGrantingStrategy, $options, $aclCache);
        
        $this->propertyChanges = new \SplObjectStorage();
    }
    
    public function createAcl(ObjectIdentityInterface $oid)
    {
        if (false !== $this->retrieveObjectIdentityPrimaryKey($oid)) {
            throw new AclAlreadyExistsException(sprintf('%s is already associated with an ACL.', $oid));
        }
        
        $this->connection->beginTransaction();
        try {
            $this->createObjectIdentity($oid);
            
            $pk = $this->retrieveObjectIdentityPrimaryKey($oid);
            $this->connection->executeQuery($this->getInsertObjectIdentityRelationSql($pk, $pk));
                        
            $this->connection->commit();
        }
        catch (\Exception $failed) {
            $this->connection->rollBack();
            
            throw $failed;
        }
            
        return $this->findAcl($oid);
    }
    
    public function deleteAcl(ObjectIdentityInterface $oid)
    {
        $this->connection->beginTransaction();
        try {
            foreach ($this->findChildren($oid) as $childAcl) {
                $this->deleteAcl($childAcl, true);
            }
            
            $oidPK = $this->retrieveObjectIdentityPrimaryKey($oid);
            
            $this->deleteAccessControlEntries($oidPK);
            $this->deleteObjectIdentityRelations($oidPK);
            $this->deleteObjectIdentity($oidPK);
        
            $this->connection->commit();
        }
        catch (\Exception $failed) {
            $this->connection->rollBack();
            
            throw $failed;
        }
        
        unset($this->loadedAcls[$oid->getIdentifier()][$oid->getType()]);
        
        if (null !== $this->aclCache) {
            $this->aclCache->evictFromCacheByIdentity($oid);
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function findAcls(array $oids, array $sids = array())
    {
        $result = parent::findAcls($oids, $sids);
        
        foreach ($result as $oid) {
            $acl = $result->offsetGet($oid);
            
            if (false === $this->propertyChanges->contains($acl) && $acl instanceof MutableAclInterface) {
                $acl->addPropertyChangedListener($this);
                $this->propertyChanges->attach($acl, array());
            }
            
            $parentAcl = $acl->getParentAcl();
            while (null !== $parentAcl) {
                if (false === $this->propertyChanges->contains($parentAcl) && $acl instanceof MutableAclInterface) {
                    $parentAcl->addPropertyChangedListener($this);
                    $this->propertyChanges->attach($parentAcl, array());    
                }
                
                $parentAcl = $parentAcl->getParentAcl();
            }
        }
        
        return $result;
    }
    
    /**
     * Implementation of PropertyChangedListener
     * 
     * This allows us to keep track of which values have been changed, so we don't
     * have to do a full introspection when ->updateAcl() is called.
     * 
     * @param mixed $sender
     * @param string $propertyName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return void
     */
    public function propertyChanged($sender, $propertyName, $oldValue, $newValue)
    {
        if (!$sender instanceof MutableAclInterface && !$sender instanceof EntryInterface) {
            throw new \InvalidArgumentException('$sender must be an instance of MutableAclInterface, or EntryInterface.');
        }
        
        if ($sender instanceof EntryInterface) {
            if (null === $sender->getId()) {
                return;
            }
            
            $ace = $sender;
            $sender = $ace->getAcl();
        }
        else {
            $ace = null;
        }
        
        if (false === $this->propertyChanges->contains($sender)) {
            throw new \InvalidArgumentException('$sender is not being tracked by this provider.');    
        }
        
        $propertyChanges = $this->propertyChanges->offsetGet($sender);
        if (null === $ace) {
            if (isset($propertyChanges[$propertyName])) {
                $oldValue = $propertyChanges[$propertyName][0];
                if ($oldValue === $newValue) {
                    unset($propertyChanges[$propertyName]);
                }
                else {
                    $propertyChanges[$propertyName] = array($oldValue, $newValue);
                }
            }
            else {
                $propertyChanges[$propertyName] = array($oldValue, $newValue);
            }
        }
        else {
            if (!isset($propertyChanges['aces'])) {
                $propertyChanges['aces'] = new \SplObjectStorage();
            }
            
            $acePropertyChanges = $propertyChanges['aces']->contains($ace)? $propertyChanges['aces']->offsetGet($ace) : array();
            
            if (isset($acePropertyChanges[$propertyName])) {
                $oldValue = $acePropertyChanges[$propertyName][0];
                if ($oldValue === $newValue) {
                    unset($acePropertyChanges[$propertyName]);
                }
                else {
                    $acePropertyChanges[$propertyName] = array($oldValue, $newValue);
                }
            }
            else {
                $acePropertyChanges[$propertyName] = array($oldValue, $newValue);
            }
            
            if (count($acePropertyChanges) > 0) {
                $propertyChanges['aces']->offsetSet($ace, $acePropertyChanges);
            }
            else {
                $propertyChanges['aces']->offsetUnset($ace);
                
                if (0 === count($propertyChanges['aces'])) {
                    unset($propertyChanges['aces']);                    
                }
            }
        }

        if (count($propertyChanges) > 0) {
            $this->propertyChanges->offsetSet($sender, $propertyChanges);
        }
        else {
            $this->propertyChanges->offsetUnset($sender);
        }
    }
    
    /**
     * Persists any changes which were made to the ACL, or any associated 
     * access control entries.
     * 
     * Changes to parent ACLs are not persisted.
     * 
     * @param ObjectIdentityInterface $oid
     * @param MutableAclInterface $acl
     * @return void
     */
    public function updateAcl(MutableAclInterface $acl)
    {
        if (null === $acl->getId()) {
            throw new \InvalidArgumentException('ACL must have an ID.');
        }
        
        // check if any changes were made to this ACL
        if (false === $this->propertyChanges->contains($acl)) {
            return;
        }
        
        $propertyChanges = $this->propertyChanges->offsetGet($acl);
        $sets = array();
        
        $this->connection->beginTransaction();
        try {
            if (isset($propertyChanges['entriesInheriting'])) {
                $sets[] = 'entries_inheriting = '.$propertyChanges['entriesInheriting'][1]? '1' : '0';
            }
            
            if (isset($propertyChanges['parentAcl'])) {
                if (null === $propertyChanges['parentAcl'][1]) {
                    $sets[] = 'parent_object_identity_id = NULL';
                }
                else {
                    $sets[] = 'parent_object_identity_id = '.$propertyChanges['parentAcl'][1]->getId();
                }
                
                $this->regenerateAncestorRelations($acl);
            }
            
            // this includes only updates of existing ACEs, but neither the creation, nor
            // the deletion of ACEs; these are tracked by changes to the ACL's respective
            // properties (classAces, classFieldAces, objectAces, objectFieldAces)
            if (isset($propertyChanges['aces'])) {
                $this->updateAces($propertyChanges['aces']);
            }
        
            $sharedPropertyChanges = array();
            if (isset($propertyChanges['classAces'])) {
                $this->updateAceProperty('classAces', $propertyChanges['classAces']);
                $sharedPropertyChanges['classAces'] = $propertyChanges['classAces'][1];
            }
            if (isset($propertyChanges['classFieldAces'])) {
                $this->updateFieldAceProperty('classFieldAces', $propertyChanges['classFieldAces']);
                $sharedPropertyChanges['classFieldAces'] = $propertyChanges['classFieldAces'][1];
            }
            if (isset($propertyChanges['objectAces'])) {
                $this->updateAceProperty('objectAces', $propertyChanges['objectAces']);
            }
            if (isset($propertyChanges['objectFieldAces'])) {
                $this->updateFieldAceProperty('objectFieldAces', $propertyChanges['objectFieldAces']);
            }
            
            // if there have been changes to shared properties, we need to synchronize other
            // ACL instances for object identities of the same type that are already in-memory
            if (count($sharedPropertyChanges) > 0) {
                $classAcesProperty = new \ReflectionProperty('Symfony\Component\Security\Acl\Domain\Acl', 'classAces');
                $classAcesProperty->setAccessible(true);
                $classFieldAcesProperty = new \ReflectionProperty('Symfony\Component\Security\Acl\Domain\Acl', 'classFieldAces');
                $classFieldAcesProperty->setAccessible(true);
                
                foreach ($this->loadedAcls[$acl->getObjectIdentity()->getType()] as $sameTypeAcl) {
                    if (isset($sharedPropertyChanges['classAces'])) {
                        $classAcesProperty->setValue($sameTypeAcl, $sharedPropertyChanges['classAces']);
                    }
                    
                    if (isset($sharedPropertyChanges['classFieldAces'])) {
                        $classFieldAcesProperty->setValue($sameTypeAcl, $sharedPropertyChanges['classFieldAces']);
                    }
                }
                
                if (null !== $this->aclCache) {
                    $this->aclCache->evictFromCacheByType($acl->getObjectIdentity()->getType());
                }
            }
            
            // persist any changes to the acl_object_identities table
            if (count($sets) > 0) {
                $this->connection->executeQuery($this->getUpdateObjectIdentitySql($acl->getId(), $sets));
            }
            
            $this->connection->commit();
        }
        catch (\Exception $failed) {
            $this->connection->rollBack();
            
            throw $failed;
        }
        
        $this->propertyChanges->offsetSet($acl, array());
        
        if (null !== $this->aclCache) {
            $this->aclCache->evictFromCacheByIdentity($acl->getObjectIdentity());
            
            foreach ($this->findChildren($acl->getObjectIdentity()) as $childOid) {
                $this->aclCache->evictFromCacheByIdentity($childOid);
            }
        }
    }
    
    protected function createObjectIdentity(ObjectIdentityInterface $oid)
    {
        $classId = $this->createOrRetrieveClassId($oid->getType());

        $this->connection->executeQuery($this->getInsertObjectIdentitySql($oid->getIdentifier(), $classId, true));
    }
    
    protected function createOrRetrieveClassId($classType)
    {
        if (false !== $id = $this->connection->executeQuery($this->getSelectClassIdSql($classType))->fetchColumn()) {
            return $id;
        }
        
        $this->connection->executeQuery($this->getInsertClassSql($classType));
        
        return $this->connection->executeQuery($this->getSelectClassIdSql($classType))->fetchColumn();        
    }
    
    protected function createOrRetrieveSecurityIdentityId(SecurityIdentityInterface $sid)
    {
        if (false !== $id = $this->connection->executeQuery($this->getSelectSecurityIdentityIdSql($sid))->fetchColumn()) {
            return $id;
        }
        
        $this->connection->executeQuery($this->getInsertSecurityIdentitySql($sid));
        
        return $this->connection->executeQuery($this->getSelectSecurityIdentityIdSql($sid))->fetchColumn();
    }
    
    protected function deleteAccessControlEntries($oidPK)
    {
        $this->connection->executeQuery($this->getDeleteAccessControlEntriesSql($oidPK));
    }
    
    protected function deleteObjectIdentity($pk)
    {
        $this->connection->executeQuery($this->getDeleteObjectIdentitySql($pk));
    }
    
    protected function deleteObjectIdentityRelations($pk)
    {
        $this->connection->executeQuery($this->getDeleteObjectIdentityRelationsSql($pk));
    }
    
    protected function getDeleteAccessControlEntriesSql($oidPK)
    {
        return sprintf(
        	'DELETE FROM %s WHERE object_identity_id = %d',
            $this->options['entry_table_name'],
            $oidPK
        );
    }
    
    protected function getDeleteAccessControlEntrySql($acePK)
    {
        return sprintf(
            'DELETE FROM %s WHERE id = %d',
            $this->options['entry_table_name'],
            $acePK
        );
    }
    
    protected function getDeleteObjectIdentitySql($pk)
    {
        return sprintf(
            'DELETE FROM %s WHERE id = %d',
            $this->options['oid_table_name'],
            $pk
        );
    }
    
    protected function getDeleteObjectIdentityRelationsSql($pk)
    {
        return sprintf(
            'DELETE FROM %s WHERE object_identity_id = %d',
            $this->options['oid_ancestors_table_name'],
            $pk
        );
    }
    
    protected function getInsertAccessControlEntrySql($classId, $objectIdentityId, $field, $aceOrder, $securityIdentityId, $strategy, $mask, $granting, $auditSuccess, $auditFailure)
    {
        $query = <<<QUERY
    		INSERT INTO %s (
    		    class_id,
    			object_identity_id, 
    			field_name, 
    			ace_order, 
    			security_identity_id, 
    			mask, 
    			granting, 
    			granting_strategy, 
    			audit_success, 
    			audit_failure
    		)
    		VALUES (%d, %s, %s, %d, %d, %d, %d, %s, %d, %d)
QUERY;
        
        return sprintf(
            $query,
            $this->options['entry_table_name'],
            $classId,
            null === $objectIdentityId? 'NULL' : intval($objectIdentityId),
            null === $field? 'NULL' : $this->connection->quote($field),
            $aceOrder,
            $securityIdentityId,
            $mask,
            $granting? 1 : 0,
            $this->connection->quote($strategy),
            $auditSuccess? 1 : 0,
            $auditFailure? 1 : 0
        );
    }
    
    protected function getInsertClassSql($classType)
    {
        return sprintf(
            'INSERT INTO %s (class_type) VALUES (%s)',
            $this->options['class_table_name'],
            $this->connection->quote($classType)
        );
    }
    
    protected function getInsertObjectIdentityRelationSql($objectIdentityId, $ancestorId)
    {
        return sprintf(
            'INSERT INTO %s (object_identity_id, ancestor_id) VALUES (%d, %d)',
            $this->options['oid_ancestors_table_name'],
            $objectIdentityId,
            $ancestorId
        );
    }
    
    protected function getInsertObjectIdentitySql($identifier, $classId, $entriesInheriting)
    {
        $query = <<<QUERY
      		INSERT INTO %s (class_id, object_identifier, entries_inheriting)
      		VALUES (%d, %s, %d)  
QUERY;

        return sprintf(
            $query,
            $this->options['oid_table_name'],
            $classId,
            $this->connection->quote($identifier),
            $entriesInheriting? 1 : 0
        );
    }
    
    protected function getInsertSecurityIdentitySql(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getUsername();
            $username = true;
        }
        else if ($sid instanceof RoleSecurityIdentity) {
            $identifier = $sid->getRole();
            $username = false;
        }
        else {
            throw new \InvalidArgumentException('$sid must either be an instance of UserSecurityIdentity, or RoleSecurityIdentity.');
        }
        
        return sprintf(
            'INSERT INTO %s (identifier, username) VALUES (%s, %d)',
            $this->options['sid_table_name'],
            $this->connection->quote($identifier),
            $username ? 1 : 0
        );
    }
    
    protected function getSelectAccessControlEntryIdSql($classId, $oid, $field, $order)
    {
        return sprintf(
            'SELECT id FROM %s WHERE class_id = %d AND %s AND %s AND ace_order = %d',
            $this->options['entry_table_name'],
            $classId,
            null === $oid ? 
                $this->connection->getDatabasePlatform()->getIsNullExpression('object_identity_id')
                : 'object_identity_id = '.intval($oid),
            null === $field ?
                $this->connection->getDatabasePlatform()->getIsNullExpression('field_name')
                : 'field_name = '.$this->connection->quote($field),
            $order
        );
    }
    
    protected function getSelectClassIdSql($classType)
    {
        return sprintf(
        	'SELECT id FROM %s WHERE class_type = %s LIMIT 1',
            $this->options['class_table_name'],
            $this->connection->quote($classType)
        );
    }
    
    protected function getSelectSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getUsername();
            $username = true;
        }
        else if ($sid instanceof RoleSecurityIdentity) {
            $identifier = $sid->getRole();
            $username = false;
        }
        else {
            throw new \InvalidArgumentException('$sid must either be an instance of UserSecurityIdentity, or RoleSecurityIdentity.');
        }
        
        return sprintf(
            'SELECT id FROM %s WHERE identifier = %s AND username = %d LIMIT 1',
            $this->options['sid_table_name'],
            $this->connection->quote($identifier),
            $username ? 1 : 0
        );
    }
    
    protected function getUpdateObjectIdentitySql($pk, array $changes)
    {
        if (0 === count($changes)) {
            throw new \InvalidArgumentException('There are no changes.');
        }
        
        return sprintf(
            'UPDATE %s SET %s WHERE id = %d',
            $this->options['oid_table_name'],
            implode(', ', $changes),
            $pk
        );
    }
    
    protected function getUpdateAccessControlEntrySql($pk, array $sets)
    {
        if (0 === count($sets)) {
            throw new \InvalidArgumentException('There are no changes.');
        }
        
        return sprintf(
            'UPDATE %s SET %s WHERE id = %d',
            $this->options['entry_table_name'],
            implode(', ', $sets),
            $pk
        );
    }
    
    /**
     * This regenerates the ancestor table which is used for fast read access.
     * 
     * @param AclInterface $acl
     * @return void
     */
    protected function regenerateAncestorRelations(AclInterface $acl)
    {
        $pk = $acl->getId();
        $this->connection->executeQuery($this->getDeleteObjectIdentityRelationsSql($pk));
        $this->connection->executeQuery($this->getInsertObjectIdentityRelationSql($pk, $pk));
        
        $parentAcl = $acl->getParentAcl();
        while (null !== $parentAcl) {
            $this->connection->executeQuery($this->getInsertObjectIdentityRelationSql($pk, $parentAcl->getId()));
            
            $parentAcl = $parentAcl->getParentAcl();
        }
    }
    
    /**
     * This processes changes on an ACE related property (classFieldAces, or objectFieldAces).
     * 
     * @param string $name
     * @param array $changes
     * @return void
     */
    protected function updateFieldAceProperty($name, array $changes)
    {
        $sids = new \SplObjectStorage();
        $classIds = new \SplObjectStorage();
        $currentIds = array();
        foreach ($changes[1] as $field => $new) {
            for ($i=0,$c=count($new); $i<$c; $i++) {
                $ace = $new[$i];
                
                if (null === $ace->getId()) {
                    if ($sids->contains($ace->getSecurityIdentity())) {
                        $sid = $sids->offsetGet($ace->getSecurityIdentity());
                    }
                    else {
                        $sid = $this->createOrRetrieveSecurityIdentityId($ace->getSecurityIdentity());
                    }
                    
                    $oid = $ace->getAcl()->getObjectIdentity();
                    if ($classIds->contains($oid)) {
                        $classId = $classIds->offsetGet($oid);
                    }
                    else {
                        $classId = $this->createOrRetrieveClassId($oid->getType());
                    }
                    
                    $objectIdentityId = $name === 'classFieldAces' ? null : $ace->getAcl()->getId();
                    
                    $this->connection->executeQuery($this->getInsertAccessControlEntrySql($classId, $objectIdentityId, $field, $i, $sid, $ace->getStrategy(), $ace->getMask(), $ace->isGranting(), $ace->isAuditSuccess(), $ace->isAuditFailure()));
                    $aceId = $this->connection->executeQuery($this->getSelectAccessControlEntryIdSql($classId, $objectIdentityId, $field, $i))->fetchColumn();
                    $this->loadedAces[$aceId] = $ace;
                    
                    $aceIdProperty = new \ReflectionProperty($ace, 'id');
                    $aceIdProperty->setAccessible(true);
                    $aceIdProperty->setValue($ace, intval($aceId));
                }
                else {
                    $currentIds[$ace->getId()] = true;
                }
            }
        }

        foreach ($changes[0] as $field => $old) {
            for ($i=0,$c=count($old); $i<$c; $i++) {
                $ace = $old[$i];
                
                if (!isset($currentIds[$ace->getId()])) {
                    $this->connection->executeQuery($this->getDeleteAccessControlEntrySql($ace->getId()));
                    unset($this->loadedAces[$ace->getId()]);
                }
            }
        }
    }
    
    /**
     * This processes changes on an ACE related property (classAces, or objectAces).
     * 
     * @param string $name
     * @param array $changes
     * @return void
     */
    protected function updateAceProperty($name, array $changes)
    {
        list($old, $new) = $changes;
        
        $sids = new \SplObjectStorage();
        $classIds = new \SplObjectStorage();
        $currentIds = array();
        for ($i=0,$c=count($new); $i<$c; $i++) {
            $ace = $new[$i];
            
            if (null === $ace->getId()) {
                if ($sids->contains($ace->getSecurityIdentity())) {
                    $sid = $sids->offsetGet($ace->getSecurityIdentity());
                }
                else {
                    $sid = $this->createOrRetrieveSecurityIdentityId($ace->getSecurityIdentity());
                }
                
                $oid = $ace->getAcl()->getObjectIdentity();
                if ($classIds->contains($oid)) {
                    $classId = $classIds->offsetGet($oid);
                }
                else {
                    $classId = $this->createOrRetrieveClassId($oid->getType());
                }
                
                $objectIdentityId = $name === 'classAces' ? null : $ace->getAcl()->getId();
                
                $this->connection->executeQuery($this->getInsertAccessControlEntrySql($classId, $objectIdentityId, null, $i, $sid, $ace->getStrategy(), $ace->getMask(), $ace->isGranting(), $ace->isAuditSuccess(), $ace->isAuditFailure()));
                $aceId = $this->connection->executeQuery($this->getSelectAccessControlEntryIdSql($classId, $objectIdentityId, null, $i))->fetchColumn();
                $this->loadedAces[$aceId] = $ace;
                
                $aceIdProperty = new \ReflectionProperty($ace, 'id');
                $aceIdProperty->setAccessible(true);
                $aceIdProperty->setValue($ace, intval($aceId));
            }
            else {
                $currentIds[$ace->getId()] = true;
            }
        }
        
        for ($i=0,$c=count($old); $i<$c; $i++) {
            $ace = $old[$i];
            
            if (!isset($currentIds[$ace->getId()])) {
                $this->connection->executeQuery($this->getDeleteAccessControlEntrySql($ace->getId()));
                unset($this->loadedAces[$ace->getId()]);
            }
        }
    }
    
    protected function updateAces(\SplObjectStorage $aces)
    {
        foreach ($aces as $ace)
        {
            $propertyChanges = $aces->offsetGet($ace);
            $sets = array();
            
            if (isset($propertyChanges['mask'])) {
                $sets[] = 'mask = '.intval($propertyChanges['mask'][1]);
            }
            if (isset($propertyChanges['strategy'])) {
                $sets[] = 'granting_strategy = '.$this->connection->quote($propertyChanges['strategy']);
            }
            if (isset($propertyChanges['aceOrder'])) {
                $sets[] = 'ace_order = '.intval($propertyChanges['aceOrder'][1]);
            }
            
            $this->connection->executeQuery($this->getUpdateAccessControlEntrySql($ace->getId(), $sets));
        }
    }
}