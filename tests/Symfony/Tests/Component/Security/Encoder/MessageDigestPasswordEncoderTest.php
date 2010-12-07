<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Security\Encoder;

use Symfony\Component\Security\Encoder\MessageDigestPasswordEncoder;

class MessageDigestPasswordEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsPasswordValid()
    {
        $encoder = new MessageDigestPasswordEncoder();
        $account = $this->getAccount(hash('sha256', 'password'), '');

        $this->assertTrue($encoder->isPasswordValid('password', $account));
    }

    public function testEncodePassword()
    {
        $encoder = new MessageDigestPasswordEncoder();
        $account = $this->getAccount(null, '');
        $this->assertSame(hash('sha256', 'password'), $encoder->encodePassword('password', $account));

        $encoder = new MessageDigestPasswordEncoder('sha256', true);
        $account = $this->getAccount(null, '');
        $this->assertSame(base64_encode(hash('sha256', 'password', true)), $encoder->encodePassword('password', $account));

        $encoder = new MessageDigestPasswordEncoder('sha256', false, 2);
        $account = $this->getAccount(null, '');
        $this->assertSame(hash('sha256', hash('sha256', 'password', true)), $encoder->encodePassword('password', $account));
    }

    /**
     * @expectedException LogicException
     */
    public function testEncodePasswordAlgorithmDoesNotExist()
    {
        $encoder = new MessageDigestPasswordEncoder('foobar');
        $account = $this->getAccount(null, null);
        $encoder->encodePassword('password', $account);
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
