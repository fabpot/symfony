<?php

namespace Symfony\Component\Security\Acl\Dbal;

use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;

/**
 * An ACL provider implementation.
 * 
 * This provider assumes that all ACLs share the same PermissionGrantingStrategy.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AclProvider implements AclProviderInterface
{
    const MAX_BATCH_SIZE = 20;
    
    protected $aclCache;
    protected $connection;
    protected $loadedAces;
    protected $loadedAcls;
    protected $options;
    protected $permissionGrantingStrategy;
    
    public function __construct(Connection $connection, PermissionGrantingStrategyInterface $permissionGrantingStrategy, array $options, AclCacheInterface $aclCache = null)
    {
        $this->aclCache = $aclCache;
        $this->connection = $connection;
        $this->loadedAces = array();
        $this->loadedAcls = array();
        $this->options = $options;
        $this->permissionGrantingStrategy = $permissionGrantingStrategy;
    }
    
    public function findChildren(ObjectIdentityInterface $parentOid)
    {
        $sql = $this->getFindChildrenSql($parentOid);
        
        $children = array();
        foreach ($this->connection->executeQuery($sql)->fetchAll() as $data) {
            $children[] = new ObjectIdentity($data['object_identifier'], $data['class_type']);
        }
        
        return $children;
    }
    
    public function findAcl(ObjectIdentityInterface $oid, array $sids = array())
    {
        return $this->findAcls(array($oid), $sids)->offsetGet($oid);
    }
    
    public function findAcls(array $oids, array $sids = array())
    {
        $result = new \SplObjectStorage();
        $currentBatch = array();
        $oidLookup = array();
        
        for ($i=0,$c=count($oids); $i<$c; $i++) {
            $oid = $oids[$i];
            $oidLookupKey = $oid->getIdentifier().$oid->getType();
            $oidLookup[$oidLookupKey] = $oid;
            $aclFound = false;
            
            // check if result already contains an ACL
            if ($result->contains($oid)) {
                $aclFound = true;
            }
            
            // check if this ACL has already been hydrated
            if (!$aclFound && isset($this->loadedAcls[$oid->getType()][$oid->getIdentifier()])) {
                $acl = $this->loadedAcls[$oid->getType()][$oid->getIdentifier()];
                
                if (!$acl->isSidLoaded($sids)) {
                    // FIXME: we need to load ACEs for the missing SIDs. This is never
                    //        reached by the default implementation, since we do not
                    //        filter by SID
                    throw new \RuntimeException('This is not supported by the default implementation.');
                }
                else {
                    $result->attach($oid, $acl);
                    $aclFound = true;
                }
            }
            
            // check if we can locate the ACL in the cache
            if (!$aclFound && null !== $this->aclCache) {
                $acl = $this->aclCache->getFromCacheByIdentity($oid);
                
                if (null !== $acl) {
                    if ($acl->isSidLoaded($sids)) {
                        $this->loadedAcls[$oid->getType()][$oid->getIdentifier()] = $acl;
                        $result->attach($oid, $acl);
                        $aclFound = true;
                    }
                    else {
                        $this->aclCache->evictFromCacheByIdentity($oid);
                    }
                }
            }
            
            // looks like we have to load the ACL from the database
            if (!$aclFound) {
                $currentBatch[] = $oid;
            }

            // Is it time to load the current batch?
            if ((self::MAX_BATCH_SIZE === count($currentBatch) || ($i + 1) === $c) && count($currentBatch) > 0) {
                $loadedBatch = $this->lookupObjectIdentities($currentBatch, $sids, $oidLookup);
                $result->addAll($loadedBatch);
                
                if (null !== $this->aclCache) {
                    foreach ($loadedBatch as $loadedAcl) {
                        $loadedOId = $loadedAcl->getObjectIdentity();
                        $this->aclCache->putInCache($loadedAcl);
                    }   
                }
                    
                $currentBatch = array();
            }
        }
        
        // check that we got ACLs for all the identities
        foreach ($oids as $oid) {
            if (!$result->contains($oid)) {
                throw new AclNotFoundException(sprintf('No ACL found for %s.', $oid));
            }
        }
        
        return $result;
    }
    
    protected function lookupObjectIdentities(array &$batch, array $sids, array &$oidLookup)
    {
        $sql = $this->getLookupSql($batch, $sids);
        $stmt = $this->connection->executeQuery($sql);
        
        return $this->hydrateObjectIdentities($stmt, $oidLookup);
    }
    
    /**
     * This method is called to hydrate ACLs and ACEs.
     * 
     * This method was designed for performance; thus, a lot of code has been
     * inlined at the cost of readability.
     * 
     * @param Statement $stmt
     * @param array $oidLookup
     * @throws \RuntimeException
     */
    protected function hydrateObjectIdentities(Statement $stmt, array &$oidLookup) {
        $parentIdToFill = new \SplObjectStorage();
        $acls = $aces = $sids = array();
        $oidCache = $oidLookup;
        $result = new \SplObjectStorage();
        
        // we need these to set protected properties on hydrated objects
        $aclReflection = new \ReflectionClass('Symfony\Component\Security\Acl\Domain\Acl');
        $aclIdProperty = $aclReflection->getProperty('id');
        $aclIdProperty->setAccessible(true);
        $aclClassAcesProperty = $aclReflection->getProperty('classAces');
        $aclClassAcesProperty->setAccessible(true);
        $aclClassFieldAcesProperty = $aclReflection->getProperty('classFieldAces');
        $aclClassFieldAcesProperty->setAccessible(true);
        $aclObjectAcesProperty = $aclReflection->getProperty('objectAces');
        $aclObjectAcesProperty->setAccessible(true);
        $aclObjectFieldAcesProperty = $aclReflection->getProperty('objectFieldAces');
        $aclObjectFieldAcesProperty->setAccessible(true);
        $aclParentAclProperty = $aclReflection->getProperty('parentAcl');
        $aclParentAclProperty->setAccessible(true);
        
        foreach ($stmt->fetchAll() as $data) {
            $aclId = $data['acl_id'];
            $aceId = $data['ace_id'];
            
            // has the ACL been hydrated during this hydration cycle?
            if (isset($acls[$aclId])) {
                $acl = $acls[$aclId];
            }
            // has the ACL been hydrated during any previous cycle, or was possibly loaded
            // from cache?
            else if (isset($this->loadedAcls[$data['class_type']][$data['object_identifier']])) {
                $acl = $this->loadedAcls[$data['class_type']][$data['object_identifier']];
                
                // keep reference in local array (saves us some hash calculations)
                $acls[$aclId] = $acl;
                
                // attach ACL to the result set; even though we do not enforce that every
                // object identity has only one instance, we must make sure to maintain 
                // referential equality with the oids passed to findAcls()
                if (!isset($oidCache[$data['object_identifier'].$data['class_type']])) {
                    $oidCache[$data['object_identifier'].$data['class_type']] = $acl->getObjectIdentity();
                }
                $result->attach($oidCache[$data['object_identifier'].$data['class_type']], $acl);
            }
            // so, this hasn't been hydrated yet
            else {
                // create object identity if we haven't done so yet
                $oidLookupKey = $data['object_identifier'].$data['class_type'];
                if (!isset($oidCache[$oidLookupKey])) {
                    $oidCache[$oidLookupKey] = new ObjectIdentity($data['object_identifier'], $data['class_type']);
                }
                
                $acl = new Acl($oidCache[$oidLookupKey], $this->permissionGrantingStrategy, array(), !!$data['entries_inheriting']);
                $this->loadedAcls[$data['class_type']][$data['object_identifier']] = $acl;
                $acls[$aclId] = $acl;
                
                $aclIdProperty->setValue($acl, intval($data['acl_id']));
                
                if (null !== $data['parent_object_identity_id']) {
                    $parentIdToFill->attach($acl, $data['parent_object_identity_id']);   
                }
                
                $result->attach($oidCache[$oidLookupKey], $acl);
            }
            
            // check if this row contains an ACE record
            if (null !== $aceId) {
                // have we already hydrated ACEs for this ACL
                if (!isset($aces[$aclId])) {
                    $aces[$aclId] = array(
                        'class' => array(),
                        'classField' => array(),
                        'object' => array(),
                        'objectField' => array(),
                    );
                }
                
                // has this ACE already been hydrated during a previous cycle, or
                // possible been loaded from cache?
                // It is important to only ever have one ACE instance per actual row since
                // some ACEs are shared between ACL instances
                if (!isset($this->loadedAces[$aceId])) {
                    $key = $data['username'].$data['security_identifier'];
                    if (!isset($sids[$key])) {
                        $sids[$key] = $data['username'] ? new UserSecurityIdentity($data['security_identifier']) : new RoleSecurityIdentity($data['security_identifier']);
                    }
                    
                    if (null === $data['field_name']) {
                        $this->loadedAces[$aceId] = new Entry(intval($aceId), $acl, $sids[$key], $data['granting_strategy'], intval($data['mask']), !!$data['granting'], !!$data['audit_failure'], !!$data['audit_success']);   
                    }
                    else {
                        $this->loadedAces[$aceId] = new FieldEntry(intval($aceId), $acl, $data['field_name'], $sids[$key], $data['granting_strategy'], intval($data['mask']), !!$data['granting'], !!$data['audit_failure'], !!$data['audit_success']);
                    }
                }
                $ace = $this->loadedAces[$aceId];
                
                // assign ACE to the correct property
                if (null === $data['object_identity_id']) {
                    if (null === $data['field_name']) {
                        $aces[$aclId]['class'][intval($data['ace_order'])] = $ace;
                    }
                    else {
                        $aces[$aclId]['classField'][$data['field_name']][intval($data['ace_order'])] = $ace;    
                    }
                }
                else {
                    if (null === $data['field_name']) {
                        $aces[$aclId]['object'][intval($data['ace_order'])] = $ace;
                    }
                    else {
                        $aces[$aclId]['objectField'][$data['field_name']][intval($data['ace_order'])] = $ace;
                    }
                }
            }
        }
        
        // We do not sort on database level since we only want certain subsets to be sorted,
        // and we are going to read the entire result set anyway.
        // Sorting on DB level increases query time by an order of magnitude while it is 
        // almost negligible when we use PHPs array sort functions.
        foreach ($aces as $aclId => $aceData) {
            $acl = $acls[$aclId];
            
            ksort($aceData['class']);
            $aclClassAcesProperty->setValue($acl, array_values($aceData['class']));
            
            foreach (array_keys($aceData['classField']) as $fieldName) {
                ksort($aceData['classField'][$fieldName]);
                $aceData['classField'][$fieldName] = array_values($aceData['classField'][$fieldName]);
            }
            $aclClassFieldAcesProperty->setValue($acl, array_values($aceData['classField']));
            
            ksort($aceData['object']);
            $aclObjectAcesProperty->setValue($acl, array_values($aceData['object']));
            
            foreach (array_keys($aceData['objectField']) as $fieldName) {
                ksort($aceData['objectField'][$fieldName]);
                $aceData['objectField'][$fieldName] = array_values($aceData['objectField'][$fieldName]);
            }
            $aclObjectFieldAcesProperty->setValue($acl, array_values($aceData['objectField']));
        }
        
        // fill-in parent ACLs where this hasn't been done yet cause the parent ACL was not
        // yet hydrated
        $processed = 0;
        foreach ($parentIdToFill as $acl)
        {
            $parentId = $parentIdToFill->offsetGet($acl);
            
            // let's see if we have already hydrated this
            if (isset($acls[$parentId])) {
                $aclParentAclProperty->setValue($acl, $acls[$parentId]);
                $processed += 1;
                
                continue;
            }
        }
        
        // this should never be true if the database integrity hasn't been compromised
        if ($processed < count($parentIdToFill)) {
            throw new \RuntimeException('Not all parent ids were populated. This implies an integrity problem.');
        }
        
        return $result;
    }
    
    protected function getLookupSql(array &$batch, array $sids)
    {
        // FIXME: add support for filtering by sids (right now we select all sids)
        
        $ancestorIds = $this->getAncestorIds($batch);
        
        if (0 === count($ancestorIds)) {
            throw new AclNotFoundException('There is no ACL for the given object identity.');
        }
        
        $sql = <<<SELECTCLAUSE
        	SELECT
        		o.id as acl_id,
        		o.object_identifier,
        		o.parent_object_identity_id,
        		o.entries_inheriting,
        		c.class_type,
        		e.id as ace_id,
        		e.object_identity_id,
        		e.field_name,
        		e.ace_order,
        		e.mask,
        		e.granting,
        		e.granting_strategy,
        		e.audit_success,
        		e.audit_failure,
        		s.username,
        		s.identifier as security_identifier
        	FROM
        		{$this->options['oid_table_name']} o
        	INNER JOIN {$this->options['class_table_name']} c ON c.id = o.class_id
        	LEFT JOIN {$this->options['entry_table_name']} e ON (
        		e.class_id = o.class_id AND (e.object_identity_id = o.id OR {$this->connection->getDatabasePlatform()->getIsNullExpression('e.object_identity_id')})
        	)
        	LEFT JOIN {$this->options['sid_table_name']} s ON (
        		s.id = e.security_identity_id
        	)
        	
        	WHERE (o.id = 
SELECTCLAUSE;

        $sql .= implode(' OR o.id = ', $ancestorIds).')';
        
        return $sql;
    }
    
    protected function getAncestorIds(array &$batch)
    {
        $sql = <<<SELECTCLAUSE
        	SELECT a.ancestor_id
        	FROM acl_object_identities o
        	INNER JOIN acl_classes c ON c.id = o.class_id
        	INNER JOIN acl_object_identity_ancestors a ON a.object_identity_id = o.id
       		WHERE (
SELECTCLAUSE;
        
        $where = '(o.object_identifier = %s AND c.class_type = %s)';
        for ($i=0,$c=count($batch); $i<$c; $i++) {
            $sql .= sprintf(
                $where,
                $this->connection->quote($batch[$i]->getIdentifier()),
                $this->connection->quote($batch[$i]->getType())
            );
            
            if ($i+1 < $c) {
                $sql .= ' OR ';
            }
        }
        
        $sql .= ')';
        
        $ancestorIds = array();
        foreach ($this->connection->executeQuery($sql)->fetchAll() as $data) {
            // FIXME: skip ancestors which are cached
            
            $ancestorIds[] = $data['ancestor_id'];
        }
        
        return $ancestorIds;
    }
    
    protected function getFindChildrenSql(ObjectIdentityInterface $oid)
    {
        $query = <<<FINDCHILDREN
			SELECT o.object_identifier, c.class_type 
			FROM 
				{$this->options['oid_table_name']} as o
			INNER JOIN {$this->options['class_table_name']} as c ON c.id = o.class_id
			INNER JOIN {$this->options['oid_ancestors_table_name']} as a ON a.object_identity_id = o.id
			WHERE 
				a.ancestor_id = %d AND a.object_identity_id != a.ancestor_id
FINDCHILDREN;

		return sprintf($query, $this->retrieveObjectIdentityPrimaryKey($oid));
    }
    
    protected function getSelectObjectIdentityIdSql(ObjectIdentityInterface $oid)
    {
        $query = <<<QUERY
        	SELECT o.id
        	FROM %s o
        	INNER JOIN %s c ON c.id = o.class_id
        	WHERE o.object_identifier = %s AND c.class_type = %s
        	LIMIT 1
QUERY;

        return sprintf(
            $query,
            $this->options['oid_table_name'],
            $this->options['class_table_name'],
            $this->connection->quote($oid->getIdentifier()),
            $this->connection->quote($oid->getType())
        );
    }
    
    protected function retrieveObjectIdentityPrimaryKey(ObjectIdentityInterface $oid)
    {
        return $this->connection->executeQuery($this->getSelectObjectIdentityIdSql($oid))->fetchColumn();
    }
}