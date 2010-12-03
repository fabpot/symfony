<?php

namespace Symfony\Tests\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\DoctrineAclCache;
use Doctrine\Common\Cache\ArrayCache;

class DoctrineAclCacheTest extends \PHPUnit_Framework_TestCase
{
    protected $permissionGrantingStrategy;
    
    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getEmptyValue
     */
    public function testConstructorDoesNotAcceptEmptyPrefix($empty)
    {
        new DoctrineAclCache(new ArrayCache(), $empty);
    }
    
    public function getEmptyValue()
    {
        return array(
            array(null),
            array(false),
            array(''),
        );
    }
    
    public function test()
    {
        $cache = $this->getCache();
        
        $aclWithParent = $this->getAcl(1);
        $acl = $this->getAcl();
        
        $cache->putInCache($aclWithParent);
        $cache->putInCache($acl);
        
        $serialized = $cache->getFromCacheByIdentity($acl->getObjectIdentity());
        var_dump($serialized);
    }

    protected function getAcl($depth = 0)
    {
        static $id = 1;
        
        $acl = new Acl($id, new ObjectIdentity($id, 'foo'), $this->getPermissionGrantingStrategy(), array(), $depth > 0);
        
        // insert some ACEs
        $sid = new UserSecurityIdentity('johannes');
        $acl->insertClassAce(0, 1, $sid, true);
        $acl->insertClassFieldAce(0, 'foo', 1, $sid, true);
        $acl->insertObjectAce(0, 1, $sid, true);
        $acl->insertObjectFieldAce(0, 'foo', 1, $sid, true);
        $id++;
        
        if ($depth > 0) {
            $acl->setParentAcl($this->getAcl($depth - 1));
        }
        
        return $acl;
    }
    
    protected function getPermissionGrantingStrategy()
    {
        if (null === $this->permissionGrantingStrategy) {
            $this->permissionGrantingStrategy = new PermissionGrantingStrategy();
        }
        
        return $this->permissionGrantingStrategy;
    }
    
    protected function getCache($cacheDriver = null, $prefix = DoctrineAclCache::PREFIX)
    {
        if (null === $cacheDriver) {
            $cacheDriver = new ArrayCache();
        }
        
        return new DoctrineAclCache($cacheDriver, $prefix);
    }
}