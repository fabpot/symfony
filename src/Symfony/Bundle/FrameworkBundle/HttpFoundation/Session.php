<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\HttpFoundation;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\SessionStorage\SessionStorageInterface;
use Symfony\Component\HttpFoundation\Session as BaseSession;

/**
 * Session, that can register its save() method on 'core.response' event
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class Session extends BaseSession
{
    /**
     * Registers session event listener
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function connect(EventDispatcherInterface $dispatcher)
    {
        $dispatcher->connect('core.response', array($this, 'filterResponse'));
    }

    /**
     * Filters response and saves session
     *
     * @param EventInterface $event
     * @param Response $response
     */
    public function filterResponse(EventInterface $event, Response $response)
    {
        $this->save();
        return $response;
    }
}
