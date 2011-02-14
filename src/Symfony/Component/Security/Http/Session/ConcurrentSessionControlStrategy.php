<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Session;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\AccountInterface;

/**
 * ConcurrentSessionControlStrategy.
 *
 * Strategy which handles concurrent session-control, in addition to the functionality provided by the base class.
 * When invoked following an authentication, it will check whether the user in question should be allowed to proceed,
 * by comparing the number of sessions they already have active with the configured maximumSessions value.
 * The SessionRegistry is used as the source of data on authenticated users and session data.
 *
 * @author Stefan Paschke <stefan.paschke@gmail.com>
 */
class ConcurrentSessionControlStrategy extends SessionAuthenticationStrategy
{
    protected $registry;
    protected $alwaysCreateSession;
    protected $exceptionIfMaximumExceeded = false;
    protected $maximumSessions;

    public function __construct($maximumSessions, SessionRegistry $registry, $sessionAuthenticationStrategy = null)
    {
        parent::__construct($sessionAuthenticationStrategy);
        $this->registry = $registry;
        $this->setMaximumSessions($maximumSessions);
    }

    /**
     * Called when a user is newly authenticated.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @return void
     */
    public function onAuthentication(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $originalSessionId = $request->getSession()->getId();

        parent::onAuthentication($request, $token);

        if ($originalSessionId != $request->getSession()->getId()) {
            $this->onSessionChange($originalSessionId, $request->getSession()->getId(), $token);
        }

        $sessions       = $this->registry->getAllSessions($user);
        $maxSessions    = $this->getMaximumSessionsForThisUser($user);

        if ($sessions->count() >= $maxSessions && $this->alwaysCreateSession !== true) {
            if ($this->exceptionIfMaximumExceeded) {
                throw new \RuntimeException(sprintf('Maximum of sessions (%s) exceeded', $maxSessions));
            }

            $this->allowableSessionsExceeded($sessions, $maxSessions, $this->registry);
        }

        $this->registry->registerNewSession($request->getSession()->getID(), $user);
    }

    /**
     * Sets a boolean flag that allows to bypass allowableSessionsExceeded().
     *
     * param boolean $alwaysCreateSession
     * @return void
     */
    public function setAlwaysCreateSession($alwaysCreateSession)
    {
        $this->alwaysCreateSession = $alwaysCreateSession;
    }

    /**
     * Sets a boolean flag that causes a RuntimeException to be thrown if the number of sessions is exceeded.
     *
     * @param boolean $exceptionIfMaximumExceeded
     * @return void
     */
    public function setExceptionIfMaximumExceeded($exceptionIfMaximumExceeded)
    {
        $this->exceptionIfMaximumExceeded = $exceptionIfMaximumExceeded;
    }

    /**
     * Sets the maxSessions property.
     *
     * @param $maximumSessions
     * @return void
     */
    public function setMaximumSessions($maximumSessions)
    {
        $this->maximumSessions = $maximumSessions;
    }

    /**
     * Allows subclasses to customise behaviour when too many sessions are detected.
     *
     * @param SessionInformationIterator $sessions
     * @param integer $allowableSessions
     * @param SessionRegistry $registry
     * @return void
     */
    protected function allowableSessionsExceeded(SessionInformationIterator $sessions, $allowableSessions, SessionRegistry $registry)
    {
        // remove oldest sessions from registry
        $count = 0;
        $sessions->sort();

        for ($i = $allowableSessions - 1; $i < $sessions->count(); $i++) {
            $sessions[$i]->expireNow();
            $registry->setSessionInformation($sessions[$i]);
        }
    }

    /**
     * Method intended for use by subclasses to override the maximum number of sessions that are permitted for a particular authentication.
     *
     * @param AccountInterface $user
     * @return integer
     */
    protected function getMaximumSessionsForThisUser(AccountInterface $user)
    {
        return $this->maximumSessions;
    }

    /**
     * Called when the session has been changed and the old attributes have been migrated to the new session.
     *
     * @param string $originalSessionId
     * @param string $newSessionId
     * @param TokenInterface $token
     * @return void
     */
    protected function onSessionChange($originalSessionId, $newSessionId, TokenInterface $token)
    {
        $this->registry->removeSessionInformation($originalSessionId, $token->getUser());
    }
}
