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

use Symfony\Component\Security\Core\User\AccountInterface;

/**
 * SessionRegistry.
 *
 * Maintains a registry of SessionInformation instances.
 *
 * @author Stefan Paschke <stefan.paschke@gmail.com>
 */
class SessionRegistry
{
    protected $sessionRegistryStorage;
    protected $sessionInformationClass;
    protected $sessionInformationIteratorClass;

    public function __construct(SessionRegistryStorageInterface $sessionRegistryStorage, $sessionInformationClass = 'Symfony\Component\Security\Http\Session\SessionInformation', $sessionInformationIteratorClass = 'Symfony\Component\Security\Http\Session\SessionInformationIterator')
    {
        $this->sessionRegistryStorage = $sessionRegistryStorage;
        $this->sessionInformationClass = $sessionInformationClass;
        $this->sessionInformationIteratorClass = $sessionInformationIteratorClass;
    }

    /**
     * Obtains all the users for which session information is stored.
     *
     * @return array An array of AccountInterface objects.
     */
    public function getAllUsers()
    {
        return $this->sessionRegistryStorage->getUsers();
    }

    /**
     * Obtains all the known sessions for the specified user.
     *
     * @param AccountInterface $user the specified user.
     * @param boolean $includeExpiredSessions.
     * @return SessionInformationIterator $sessions the known sessions.
     */
    public function getAllSessions(AccountInterface $user, $includeExpiredSessions = false)
    {
        $sessions = new $this->sessionInformationIteratorClass();

        foreach ($this->sessionRegistryStorage->getSessionIds($user) as $sessionId) {
            if ($sessionInformation = $this->getSessionInformation($sessionId)) {
                if ($includeExpiredSessions === true || $sessionInformation->isExpired() === false) {
                    $sessions->add($sessionInformation);
                }
            }
        }

        return $sessions;
    }

    /**
     * Obtains the session information for the specified sessionId.
     *
     * @param string $sessionId the session identifier key.
     * @return SessionInformation $sessionInformation
     */
    public function getSessionInformation($sessionId)
    {
        return $this->sessionRegistryStorage->getSessionInformation($sessionId);
    }

    /**
     * Sets a SessionInformation object.
     *
     * @param SessionInformation $sessionInformation
     * @return void
     */
    public function setSessionInformation(SessionInformation $sessionInformation)
    {
        $this->sessionRegistryStorage->setSessionInformation($sessionInformation->getSessionId(), $sessionInformation);
    }

    /**
     * Updates the given sessionId so its last request time is equal to the present date and time.
     *
     * @param string $sessionId the session identifier key.
     * @return void
     */
    public function refreshLastRequest($sessionId)
    {
        if ($sessionInformation = $this->getSessionInformation($sessionId)) {
            $sessionInformation->refreshLastRequest();
            $this->sessionRegistryStorage->setSessionInformation($sessionInformation);
        }
    }

    /**
     * Registers a new session for the specified user.
     *
     * @param string $sessionId the session identifier key.
     * @param AccountInterface $user the specified user.
     * @return void
     */
    public function registerNewSession($sessionId, AccountInterface $user)
    {
        $sessionInformation = new $this->sessionInformationClass($sessionId, $user);
        $sessionInformation->refreshLastRequest();

        $this->sessionRegistryStorage->setSessionInformation($sessionId, $sessionInformation);
        $this->sessionRegistryStorage->addSessionId($sessionId, $user);
    }

    /**
     * Deletes all the session information being maintained for the specified sessionId.
     *
     * @param string $sessionId the session identifier key.
     * @param AccountInterface $user the specified user.
     * @return void
     */
    public function removeSessionInformation($sessionId, AccountInterface $user)
    {
        $this->sessionRegistryStorage->removeSessionInformation($sessionId);
        $this->sessionRegistryStorage->removeSessionId($sessionId, $user);
    }
}
