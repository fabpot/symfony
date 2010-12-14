<?php

namespace Symfony\Tests\Component\Security\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;

class PermissionGrantingStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers:Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy::getAuditLogger
     * @covers:Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy::setAuditLogger
     */
    public function testGetSetAuditLogger()
    {
        $strategy = new PermissionGrantingStrategy();
        $logger = $this->getMock('Symfony\Component\Security\Acl\Model\AuditLoggerInterface');

        $this->assertNull($strategy->getAuditLogger());
        $strategy->setAuditLogger($logger);
        $this->assertSame($logger, $strategy->getAuditLogger());
    }

    public function testIsGrantedObjectAcesHavePriority()
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');

        $acl->insertClassAce(0, 1, $sid, true);
        $acl->insertObjectAce(0, 1, $sid, false);
        $this->assertFalse($strategy->isGranted($acl, array(1), array($sid)));
    }

    public function testIsGrantedFallsbackToClassAcesIfNoApplicableObjectAceWasFound()
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');

        $acl->insertClassAce(0, 1, $sid, true);
        $this->assertTrue($strategy->isGranted($acl, array(1), array($sid)));
    }

    public function testIsGrantedFavorsLocalAcesOverParentAclAces()
    {
        $strategy = new PermissionGrantingStrategy();
        $sid = new UserSecurityIdentity('johannes');

        $acl = $this->getAcl($strategy);
        $acl->insertClassAce(0, 1, $sid, true);

        $parentAcl = $this->getAcl($strategy);
        $acl->setParentAcl($parentAcl);
        $parentAcl->insertClassAce(0, 1, $sid, false);

        $this->assertTrue($strategy->isGranted($acl, array(1), array($sid)));
    }

    public function testIsGrantedFallsBackToParentAcesIfNoLocalAcesAreApplicable()
    {
        $strategy = new PermissionGrantingStrategy();
        $sid = new UserSecurityIdentity('johannes');
        $anotherSid = new UserSecurityIdentity('ROLE_USER');

        $acl = $this->getAcl($strategy);
        $acl->insertClassAce(0, 1, $anotherSid, false);

        $parentAcl = $this->getAcl($strategy);
        $acl->setParentAcl($parentAcl);
        $parentAcl->insertClassAce(0, 1, $sid, true);

        $this->assertTrue($strategy->isGranted($acl, array(1), array($sid)));
    }

    /**
     * @expectedException Symfony\Component\Security\Acl\Exception\NoAceFoundException
     */
    public function testIsGrantedReturnsExceptionIfNoAceIsFound()
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');

        $strategy->isGranted($acl, array(1), array($sid));
    }

    public function testIsGrantedFirstApplicableEntryMakesUltimateDecisionForPermissionIdentityCombination()
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');
        $aSid = new RoleSecurityIdentity('ROLE_USER');

        $acl->insertClassAce(0, 1, $aSid, true);
        $acl->insertClassAce(1, 1, $sid, false);
        $acl->insertClassAce(2, 1, $sid, true);
        $this->assertFalse($strategy->isGranted($acl, array(1), array($sid, $aSid)));
        
        $acl->insertObjectAce(0, 1, $sid, false);
        $acl->insertObjectAce(1, 1, $aSid, true);
        $this->assertFalse($strategy->isGranted($acl, array(1), array($sid, $aSid)));
    }

    public function testIsGrantedCallsAuditLoggerOnGrant()
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');

        $logger = $this->getMock('Symfony\Component\Security\Acl\Model\AuditLoggerInterface');
        $logger
            ->expects($this->once())
            ->method('logIfNeeded')
        ;
        $strategy->setAuditLogger($logger);

        $acl->insertObjectAce(0, 1, $sid, true);
        $acl->updateObjectAuditing(0, true, false);

        $this->assertTrue($strategy->isGranted($acl, array(1), array($sid)));
    }

    public function testIsGrantedCallsAuditLoggerOnDeny()
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');

        $logger = $this->getMock('Symfony\Component\Security\Acl\Model\AuditLoggerInterface');
        $logger
            ->expects($this->once())
            ->method('logIfNeeded')
        ;
        $strategy->setAuditLogger($logger);

        $acl->insertObjectAce(0, 1, $sid, false);
        $acl->updateObjectAuditing(0, false, true);

        $this->assertFalse($strategy->isGranted($acl, array(1), array($sid)));
    }

    /**
     * @dataProvider getAllStrategyTests
     */
    public function testIsGrantedStrategies($maskStrategy, $aceMask, $requiredMask, $result)
    {
        $strategy = new PermissionGrantingStrategy();
        $acl = $this->getAcl($strategy);
        $sid = new UserSecurityIdentity('johannes');

        $acl->insertObjectAce(0, $aceMask, $sid, true, $maskStrategy);

        if (false === $result) {
            try {
                $strategy->isGranted($acl, array($requiredMask), array($sid));
                $this->fail('The ACE is not supposed to match.');
            } catch (NoAceFoundException $noAce) { }
        } else {
            $this->assertTrue($strategy->isGranted($acl, array($requiredMask), array($sid)));
        }
    }

    public function getAllStrategyTests()
    {
        return array(
            array('all', 1 << 0 | 1 << 1, 1 << 0, true),
            array('all', 1 << 0 | 1 << 1, 1 << 2, false),
            array('all', 1 << 0 | 1 << 10, 1 << 0 | 1 << 10, true),
            array('all', 1 << 0 | 1 << 1, 1 << 0 | 1 << 1 || 1 << 2, false),
            array('any', 1 << 0 | 1 << 1, 1 << 0, true),
            array('any', 1 << 0 | 1 << 1, 1 << 0 | 1 << 2, true),
            array('any', 1 << 0 | 1 << 1, 1 << 2, false),
            array('equal', 1 << 0 | 1 << 1, 1 << 0, false),
            array('equal', 1 << 0 | 1 << 1, 1 << 1, false),
            array('equal', 1 << 0 | 1 << 1, 1 << 0 | 1 << 1, true),
        );
    }

    protected function getAcl($strategy)
    {
        static $id = 1;
        return new Acl($id++, new ObjectIdentity(1, 'Foo'), $strategy, array(), true);
    }
}