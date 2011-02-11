<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Firewall;

use Symfony\Component\EventDispatcher\EventInterface;
use Symfony\Component\Security\Http\Session\SessionRegistry;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class ConcurrentSessionListener extends ContextListener
{
    protected $sessionRegistry;

    public function __construct(SecurityContext $context, array $userProviders, $contextKey, SessionRegistry $sessionRegistry, LoggerInterface $logger = null)
    {
        parent::__construct($context, $userProviders, $contextKey, $logger);

        $this->sessionRegistry = $sessionRegistry;
    }

    /**
     * Reads the Token from the session, checks if the session is marked as expired in the session registry.
     *
     * @param EventInterface $event An EventInterface instance
     */
    public function read(EventInterface $event)
    {
        $request = $event->get('request');
        $session = $request->getSession();

        $token = $this->getToken($session);

        if (null !== $token) {
            if (null === $sessionInformation = $this->sessionRegistry->getSessionInformation($session->getId())) {
                $sessions = $this->sessionRegistry->getAllSessions($token->getUser());
                if ($sessions->count() > 0) {
                    $token = null;
                }
            } elseif ($sessionInformation->isExpired()) {
                $this->sessionRegistry->removeSessionInformation($session->getId(), $token->getUser());
                $session->invalidate();
                $token = null;
            }
        }

        if (null !== $token && false === $token->isImmutable()) {
            $token = $this->refreshUser($token);
        }

        $this->context->setToken($token);
    }
}
