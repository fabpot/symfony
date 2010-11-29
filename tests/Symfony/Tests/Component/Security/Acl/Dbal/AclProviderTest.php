<?php

namespace Symfony\Tests\Component\Security\Acl\Dbal;

use Symfony\Component\Security\Acl\Dbal\AclProvider;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Dbal\Schema;
use Doctrine\DBAL\DriverManager;

class AclProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $con;
    protected $insertClassStmt;
    protected $insertEntryStmt;
    protected $insertOidStmt;
    protected $insertOidAncestorStmt;
    protected $insertSidStmt;
    
//    public function testFindAclById()
//    {
//        $oid = new ObjectIdentity('1', 'foo');
//        $provider = $this->getProvider();
//        
//        $acl = $provider->findAcl($oid);
//        
//        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\Acl', $acl);
//        $this->assertEquals(4, $acl->getId());
//        $this->assertEquals(0, count($acl->getClassAces()));
//        $this->assertEquals(0, count($acl->getClassFieldAces()));
//        $this->assertEquals(1, count($acl->getObjectAces()));
//        $this->assertEquals(0, count($acl->getObjectFieldAces()));
//        
//        $aces = $acl->getObjectAces();
//        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\Entry', $aces[0]);
//        $this->assertTrue($aces[0]->isGranting());
//        $this->assertTrue($aces[0]->isAuditSuccess());
//        $this->assertTrue($aces[0]->isAuditFailure());
//        $this->assertEquals('all', $aces[0]->getStrategy());
//        $this->assertSame(2, $aces[0]->getMask());
//        
//        $sid = $aces[0]->getSecurityIdentity();
//        $this->assertInstanceOf('Symfony\Component\Security\Acl\Domain\UserSecurityIdentity', $sid);
//        $this->assertEquals('john.doe', $sid->getUsername());
//    }
    
    public function testBenchmarks()
    {
        $this->generateTestData();
        
        // get some identities
        $oids = array();
        foreach ($this->con->query('SELECT object_identifier, class_type FROM acl_object_identities o INNER JOIN acl_classes c ON c.id = o.class_id LIMIT '.rand(0, 10000).', 5')->fetchAll() as $data) {
            $ancestors = array();
            
            // this query takes 5ms on average
            $startTime = microtime(true);
            foreach ($this->con->query(sprintf('
            	SELECT a.ancestor_id FROM acl_object_identities o
            	INNER JOIN acl_classes c ON c.id = o.class_id
            	INNER JOIN acl_object_identity_ancestors a ON a.object_identity_id = o.id
            	WHERE o.object_identifier = %s AND c.class_type = %s
            ', $this->con->quote($data['object_identifier']), $this->con->quote($data['class_type'])))
                 as $subData) 
            {
                $ancestors[] = $subData['ancestor_id'];
            }
            $ancestorTime = microtime(true) - $startTime;
            
            // inlined query using IN()
            $startTime = microtime(true);
            $this->con->query('
            	SELECT o.object_identifier, c.class_type, e.id, s.id
            	FROM acl_object_identities o
            	INNER JOIN acl_classes c ON c.id = o.class_id
            	LEFT JOIN acl_entries e ON (
            		e.class_id = o.class_id AND (e.object_identity_id = o.id OR e.object_identity_id IS NULL)
            	)
            	LEFT JOIN acl_security_identities s ON s.id = e.security_identity_id
            	WHERE o.id IN ('.implode(',', $ancestors).')
            ');
            $inlineInTime = microtime(true) - $startTime + $ancestorTime;
            $inlinedInTimes[] = $inlineInTime; 
            
            // inlined query using OR
            $startTime = microtime(true);
            $orSql = '
            	SELECT o.object_identifier, c.class_type, e.id, s.id
            	FROM acl_object_identities o
            	INNER JOIN acl_classes c ON c.id = o.class_id
            	LEFT JOIN acl_entries e ON (
            		e.class_id = o.class_id AND (e.object_identity_id = o.id OR e.object_identity_id IS NULL)
            	)
            	LEFT JOIN acl_security_identities s ON s.id = e.security_identity_id
            	WHERE o.id = '.implode(' OR o.id = ', $ancestors).'
            ';
            $this->con->query($orSql);
            $inlineOrTime = microtime(true) - $startTime + $ancestorTime;
            $inlinedOrTimes[] = $inlineOrTime;
            
            // with subquery
            $startTime = microtime(true);
            $subSql = sprintf('
            	SELECT o.object_identifier, c.class_type, e.id, s.id
            	FROM acl_object_identities o            	
            	INNER JOIN acl_classes c ON c.id = o.class_id
            	LEFT JOIN acl_entries e ON (
            		e.class_id = o.class_id AND (e.object_identity_id = o.id OR e.object_identity_id IS NULL)
         		)
            	LEFT JOIN acl_security_identities s ON s.id = e.security_identity_id
            	WHERE o.id IN (
            		SELECT a.ancestor_id FROM acl_object_identities so
            		INNER JOIN acl_classes sc ON sc.id = so.class_id
            		INNER JOIN acl_object_identity_ancestors a ON a.object_identity_id = so.id
            		WHERE so.object_identifier = %s AND sc.class_type = %s
            	)
            ', $this->con->quote($data['object_identifier']), $this->con->quote($data['class_type']));
            $this->con->query($subSql);
            $subQueryTime = microtime(true) - $startTime;
            $subQueryTimes[] = $subQueryTime;
            
            echo "InlineIn: ".$inlineInTime."s, InlineOr: ".$inlineOrTime."s, SubQuery: ".$subQueryTime."s\n";
        }
        
        echo "InlineIn AVG: ".(array_sum($inlinedInTimes)/count($inlinedInTimes))."s\n";
        echo "InlineOr AVG: ".(array_sum($inlinedOrTimes)/count($inlinedOrTimes))."s\n";
        echo "SubQuery AVG: ".(array_sum($subQueryTimes)/count($subQueryTimes))."s\n";
    }
    
    
    public function setUp()
    {
//        $this->con = DriverManager::getConnection(array(
//            'driver' => 'pdo_sqlite',
//            'memory' => true,
//        ));
        $this->con = DriverManager::getConnection(array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'user' => 'root',
            'dbname' => 'testdb',
        ));
        
        $sm = $this->con->getSchemaManager();
        $sm->dropAndCreateDatabase('testdb');
        $this->con->exec("USE testdb");
        
        // import the schema
        $schema = new Schema($options = $this->getOptions());
        foreach ($schema->toSql($this->con->getDatabasePlatform()) as $sql) {
            $this->con->exec($sql);
        }
        
        // populate the schema with some test data
        $this->insertClassStmt = $this->con->prepare('INSERT INTO acl_classes (id, class_type) VALUES (?, ?)');
        foreach ($this->getClassData() as $data) {
            $this->insertClassStmt->execute($data);
        }
        
        $this->insertSidStmt = $this->con->prepare('INSERT INTO acl_security_identities (id, identifier, username) VALUES (?, ?, ?)');
        foreach ($this->getSidData() as $data) {
            $this->insertSidStmt->execute($data);
        }
        
        $this->insertOidStmt = $this->con->prepare('INSERT INTO acl_object_identities (id, class_id, object_identifier, parent_object_identity_id, entries_inheriting) VALUES (?, ?, ?, ?, ?)');
        foreach ($this->getOidData() as $data) {
            $this->insertOidStmt->execute($data);
        }
        
        $this->insertEntryStmt = $this->con->prepare('INSERT INTO acl_entries (id, class_id, object_identity_id, field_name, ace_order, security_identity_id, mask, granting, granting_strategy, audit_success, audit_failure) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($this->getEntryData() as $data) {
            $this->insertEntryStmt->execute($data);
        }
        
        $this->insertOidAncestorStmt = $this->con->prepare('INSERT INTO acl_object_identity_ancestors (object_identity_id, ancestor_id) VALUES (?, ?)');
        foreach ($this->getOidAncestorData() as $data) {
            $this->insertOidAncestorStmt->execute($data);
        }
    }

    public function tearDown()
    {
        $this->con = null;
    }
    
    /**
     * This generates a huge amount of test data to be used mainly for benchmarking
     * purposes, not so much for testing. That's why it's not called by default.
     */
    protected function generateTestData()
    {
        for ($i=0; $i<40000; $i++) {
            $this->generateAclHierarchy();
        }
    }
    
    protected function generateAclHierarchy()
    {
        $rootId = $this->generateAcl($this->chooseClassId(), null, array());
        
        $this->generateAclLevel(rand(1, 15), $rootId, array($rootId));
    }
    
    protected function generateAclLevel($depth, $parentId, $ancestors)
    {
        $level = count($ancestors);
        for ($i=0,$t=rand(1, 10); $i<$t; $i++) {
            $id = $this->generateAcl($this->chooseClassId(), $parentId, $ancestors);
            
            if ($level < $depth) {
                $this->generateAclLevel($depth, $id, array_merge($ancestors, array($id)));
            }
        }
    }
    
    protected function chooseClassId()
    {
        static $id = 1000;
        
        if ($id === 1000 || ($id < 1500 && rand(0, 1))) {
            $this->insertClassStmt->execute(array($id, $this->getRandomString(rand(20, 100), 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789\\_')));
            $id += 1;
            
            return $id-1;
        }
        else {
            return rand(1000, $id-1);
        }
    }
    
    protected function generateAcl($classId, $parentId, $ancestors)
    {
        static $id = 1000;
        
        $this->insertOidStmt->execute(array(
            $id,
            $classId,
            $this->getRandomString(rand(20, 50)),
            $parentId,
            rand(0, 1),
        ));
        
        $this->insertOidAncestorStmt->execute(array($id, $id));
        foreach ($ancestors as $ancestor) {
            $this->insertOidAncestorStmt->execute(array($id, $ancestor));
        }
        
        $this->generateAces($classId, $id);
        $id += 1;
        
        return $id-1;
    }
    
    protected function chooseSid()
    {
        static $id = 1000;
        
        if ($id === 1000 || ($id < 11000 && rand(0, 1))) {
            $this->insertSidStmt->execute(array(
                $id, 
                $this->getRandomString(rand(5, 30)), 
                rand(0, 1)
            ));
            $id += 1;
            
            return $id-1;
        }
        else {
            return rand(1000, $id-1);
        }
    }
    
    protected function generateAces($classId, $objectId)
    {
        static $id = 1000;
        
        $sids = array();
        $fieldOrder = array();
        
        for ($i=0; $i<=30; $i++) {
            $fieldName = rand(0, 1) ? null : $this->getRandomString(rand(10, 20));
            
            do {
                $sid = $this->chooseSid();
            }
            while (array_key_exists($sid, $sids) && in_array($fieldName, $sids[$sid], true));
            
            $fieldOrder[$fieldName] = array_key_exists($fieldName, $fieldOrder) ? $fieldOrder[$fieldName]+1 : 0;
            if (!isset($sids[$sid])) {
                $sids[$sid] = array();
            }
            $sids[$sid][] = $fieldName;
            
            $strategy = rand(0, 2);
            if ($strategy === 0) {
                $strategy = PermissionGrantingStrategy::ALL;
            }
            else if ($strategy === 1) {
                $strategy = PermissionGrantingStrategy::ANY;
            }
            else {
                $strategy = PermissionGrantingStrategy::EQUAL;
            }
            
            // id, cid, oid, field, order, sid, mask, granting, strategy, a success, a failure
            $this->insertEntryStmt->execute(array(
                $id,
                $classId,
                rand(0, 5) ? $objectId : null,
                $fieldName,
                $fieldOrder[$fieldName],
                $sid,
                $this->generateMask(),
                rand(0, 1),
                $strategy,
                rand(0, 1),
                rand(0, 1),
            ));
            
            $id += 1;
        }
    }
    
    protected function generateMask()
    {
        $i = rand(1, 30);
        $mask = 0;
        
        while ($i <= 30) {
            $mask |= 1 << rand(0, 30);
            $i++;
        }
        
        return $mask;
    }
    
    protected function getRandomString($length, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $s = '';
        $cLength = strlen($chars);
        
        while (strlen($s) < $length) {
            $s .= $chars[mt_rand(0, $cLength-1)];
        }
        
        return $s;
    }
    
    protected function getEntryData()
    {
        // id, cid, oid, field, order, sid, mask, granting, strategy, a success, a failure
        return array(
            array(1, 1, 1, null, 0, 1, 1, 1, 'all', 1, 1),
            array(2, 1, 1, null, 1, 2, 1 << 2 | 1 << 1, 0, 'any', 0, 0),
            array(3, 3, 4, null, 0, 1, 2, 1, 'all', 1, 1),
        );
    }
    
    protected function getOidData()
    {
        return array(
            array(1, 1, '123', null, 1),
            array(2, 2, '123', 1, 1),
            array(3, 2, 'i:3:123', 1, 1),
            array(4, 3, '1', 2, 1),
        );
    }
    
    protected function getOidAncestorData()
    {
        return array(
            array(1, 1),
            array(2, 1),
            array(2, 2),
            array(3, 1),
            array(3, 3),
            array(4, 2),
            array(4, 1),
            array(4, 4),
        );
    }
    
    protected function getSidData()
    {
        return array(
            array(1, 'john.doe', 1),
            array(2, 'john.doe@foo.com', 1),
            array(3, '123', 1),
            array(4, 'ROLE_USER', 1),
            array(5, 'ROLE_USER', 0),
            array(6, 'IS_AUTHENTICATED_FULLY', 0),
        );
    }
    
    protected function getClassData()
    {
        return array(
            array(1, 'Bundle\SomeVendor\MyBundle\Entity\SomeEntity'),
            array(2, 'Bundle\MyBundle\Entity\AnotherEntity'),
            array(3, 'foo'),
        );
    }
    
    protected function getOptions()
    {
        return array(
            'oid_table_name' => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'class_table_name' => 'acl_classes',
            'sid_table_name' => 'acl_security_identities',
            'entry_table_name' => 'acl_entries',
        );
    }
    
    protected function getStrategy()
    {
        return new PermissionGrantingStrategy();
    }
    
    protected function getProvider()
    {
        return new AclProvider($this->con, $this->getStrategy(), $this->getOptions());
    }
}