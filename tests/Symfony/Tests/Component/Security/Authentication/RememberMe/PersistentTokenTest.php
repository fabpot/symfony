<?php

namespace Symfony\Tests\Component\Security\Authentication\RememberMe;

use Symfony\Component\Security\Authentication\RememberMe\PersistentToken;

class PersistentTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $lastUsed = new \DateTime();
        $token = new PersistentToken('fooname', 'fooseries', 'footokenvalue', $lastUsed);
        
        $this->assertEquals($token->getUsername(), 'fooname');
        $this->assertEquals($token->getSeries(), 'fooseries');
        $this->assertEquals($token->getTokenValue(), 'footokenvalue');
        $this->assertSame($token->getLastUsed(), $lastUsed);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedMessage $username cannot be empty.
     */
    public function testConstructorUsernameCannotBeEmpty()
    {
        new PersistentToken('', 'foo', 'foo', new \DateTime());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedMessage $series cannot be empty.
     */
    public function testConstructorSeriesCannotBeEmpty()
    {
        new PersistentToken('foo', '', 'foo', new \DateTime());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedMessage $tokenValue cannot be empty.
     */
    public function testConstructorTokenValueCannotBeEmpty()
    {
        new PersistentToken('foo', 'foo', '', new \DateTime());
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testConstructorLastUsedCannotBeNull()
    {
        new PersistentToken('foo', 'foo', 'foo', null);
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testConstructorLastUsedMustBeDateTime()
    {
        new PersistentToken('foo', 'foo', 'foo', new \stdClass());
    }
}