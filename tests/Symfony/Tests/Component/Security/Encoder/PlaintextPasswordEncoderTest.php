<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Security\Encoder;

use Symfony\Component\Security\Encoder\PlaintextPasswordEncoder;

class PlaintextPasswordEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsPasswordValid()
    {
        $encoder = new PlaintextPasswordEncoder();

        $this->assertSame(true, $encoder->isPasswordValid('foo', $this->getAccount('foo', '')));
        $this->assertSame(false, $encoder->isPasswordValid('foo', $this->getAccount('bar', '')));
        $this->assertSame(false, $encoder->isPasswordValid('foo', $this->getAccount('FOO', '')));

        $encoder = new PlaintextPasswordEncoder(true);

        $this->assertSame(true, $encoder->isPasswordValid('foo', $this->getAccount('foo', '')));
        $this->assertSame(false, $encoder->isPasswordValid('foo', $this->getAccount('bar', '')));
        $this->assertSame(true, $encoder->isPasswordValid('foo', $this->getAccount('FOO', '')));
    }

    public function testEncodePassword()
    {
        $encoder = new PlaintextPasswordEncoder();

        $this->assertSame('foo', $encoder->encodePassword('foo', $this->getAccount(null, '')));
    }
    
    protected function getAccount($password = null, $salt = null)
    {
    	$mock = $this->getMock('Symfony\Component\Security\User\AccountInterface');
    	
    	if (null === $password) {
    		$mock
    			->expects($this->never())
    			->method('getPassword')
    		;
    	}
    	else {
    		$mock
    			->expects($this->once())
    			->method('getPassword')
    			->will($this->returnValue($password))
    		;
    	}
    	
    	if (null === $salt) {
    		$mock
    			->expects($this->never())
    			->method('getSalt')
    		;
    	}
    	else {
    		$mock
    			->expects($this->once())
    			->method('getSalt')
    			->will($this->returnValue($salt))
    		;
    	}
    	
    	return $mock;
    }
}
