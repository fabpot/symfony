<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\HttpFoundation;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\HttpFoundation\Session;

/**
 * SessionTest
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Symfony\Bundle\FrameworkBundle\HttpFoundation\Session::connect
     */
    public function testShouldRegisterEventListener()
    {
        $dispatcher = $this->getEventDispatcher();
        $storage    = $this->getSessionStorage();
        $session    = new Session($storage);

        $dispatcher->expects($this->once())
            ->method('connect')
            ->with('core.response', array($session, 'filterResponse'));

        $session->connect($dispatcher);
    }

    /**
     * @covers Symfony\Bundle\FrameworkBundle\HttpFoundation\Session::filterResponse
     */
    public function testFilterResponseShouldSaveSessionAndReturnResponse()
    {
        $session  = new SessionStub($this->getSessionStorage());
        $response = new Response();
        $event    = new Event($this, 'core.response');

        $this->assertFalse($session->saved);

        $this->assertSame($response, $session->filterResponse($event, $response));

        $this->assertTrue($session->saved);
    }

    /**
     * @return Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    /**
     * @return Symfony\Component\HttpFoundation\SessionStorage\SessionStorageInterface
     */
    private function getSessionStorage()
    {
        return $this->getMock('Symfony\Component\HttpFoundation\SessionStorage\SessionStorageInterface');
    }
}
