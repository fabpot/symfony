<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Security\Encoder;

class BasePasswordEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testComparePassword()
    {
        $this->assertTrue($this->invokeComparePasswords('password', 'password'));
        $this->assertFalse($this->invokeComparePasswords('password', 'foo'));
    }

    public function testDemergePasswordAndSalt()
    {
        $this->assertEquals(array('password', 'salt'), $this->invokeDemergePasswordAndSalt('password{salt}'));
        $this->assertEquals(array('password', ''), $this->invokeDemergePasswordAndSalt('password'));
        $this->assertEquals(array('', ''), $this->invokeDemergePasswordAndSalt(''));
    }

    public function testMergePasswordAndSalt()
    {
        $this->assertEquals('password{salt}', $this->invokeMergePasswordAndSalt('password', 'salt'));
        $this->assertEquals('password', $this->invokeMergePasswordAndSalt('password', ''));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMergePasswordAndSaltWithException()
    {
        $this->invokeMergePasswordAndSalt('password', '{foo}');
    }

    protected function invokeDemergePasswordAndSalt($password)
    {
        $encoder = $this->getEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('demergePasswordAndSalt');
        $m->setAccessible(true);

        return $m->invoke($encoder, $password);
    }

    protected function invokeMergePasswordAndSalt($password, $salt)
    {
        $encoder = $this->getEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('mergePasswordAndSalt');
        $m->setAccessible(true);

        return $m->invoke($encoder, $password, $salt);
    }

    protected function invokeComparePasswords($p1, $p2)
    {
        $encoder = $this->getEncoder();
        $r = new \ReflectionObject($encoder);
        $m = $r->getMethod('comparePasswords');
        $m->setAccessible(true);

        return $m->invoke($encoder, $p1, $p2);
    }
    
    protected function getEncoder()
    {
    	return $this->getMockForAbstractClass('Symfony\Component\Security\Encoder\BasePasswordEncoder');
    }
}
