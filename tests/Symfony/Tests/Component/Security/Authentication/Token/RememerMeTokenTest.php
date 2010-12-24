<?php

namespace Symfony\Tests\Component\Security\Authentication\Token;

use Symfony\Component\Security\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Role\Role;

class RememberMeTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $user = $this->getUser();
        $token = new RememberMeToken($user, 'foo');
        
        $this->assertEquals($token->getKey(), 'foo');
        $this->assertEquals($token->getRoles(), array(new Role('ROLE_FOO')));
        $this->assertSame($user, $token->getUser());
        $this->assertTrue($token->isAuthenticated());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorKeyCannotBeNull()
    {
        new RememberMeToken(
            $this->getUser(),
            null
        );
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorKeyCannotBeEmptyString()
    {
        new RememberMeToken(
            $this->getUser(),
            ''
        );
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructorUserCannotBeNull()
    {
        new RememberMeToken(null, 'foo');
    }
    
    public function testPersistentToken()
    {
        $token = new RememberMeToken($this->getUser(), 'foo');
        $persistentToken = $this->getMock('Symfony\Component\Security\Authentication\RememberMe\PersistentTokenInterface');
        
        $this->assertNull($token->getPersistentToken());
        $token->setPersistentToken($persistentToken);
        $this->assertSame($persistentToken, $token->getPersistentToken());
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testSetPersistentTokenRequiresPersistentTokenInterface()
    {
        $token = new RememberMeToken($this->getUser(), 'foo');
        
        $token->setPersistentToken(null);
    }
    
    protected function getUser($roles = array('ROLE_FOO'))
    {
        $user = $this->getMock('Symfony\Component\Security\User\AccountInterface');
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles))
        ;
        
        return $user;
    }
}