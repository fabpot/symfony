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
 * SessionRegistryStorageInterface.
 *
 * Stores the SessionInformation instances maintained in the SessionRegistry.
 *
 * @author Stefan Paschke <stefan.paschke@gmail.com>
 */
interface SessionRegistryStorageInterface
{
    /**
     * Obtains all the users for which session information is stored.
     *
     * @return array An array of AccountInterface objects.
     */
    function getUsers();

    /**
     * Obtains all the known session IDs for the specified user.
     *
     * @param AccountInterface $user
     * @return array an array ob session identifiers.
     */
    function getSessionIds(AccountInterface $user);

    /**
     * Adds one session ID to the specified users array
     *
     * @param string $sessionId the session identifier key.
     * @param AccountInterface $user
     * @return void
     */
    function addSessionId($sessionId, AccountInterface $user);

    /**
     * Removes one session ID from the specified users array.
     *
     * @param string $sessionId the session identifier key.
     * @param AccountInterface $user
     * @return void
     */
    function removeSessionId($sessionId, AccountInterface $user);

    /**
     * Obtains the maintained information for one session.
     *
     * @param string $sessionId the session identifier key.
     * @return SessionInformation a SessionInformation object.
     */
    function getSessionInformation($sessionId);

    /**
     * Adds information for one session.
     *
     * @param string $sessionId the session identifier key.
     * @param SessionInformation a SessionInformation object.
     * @return void
     */
    function setSessionInformation($sessionId, SessionInformation $sessionInformation);

    /**
     * Deletes the maintained information of one session.
     *
     * @param string $sessionId the session identifier key.
     * @return void
     */
    function removeSessionInformation($sessionId);
}
