<?php

namespace Symfony\Component\Security\Encoder;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Security\User\AccountInterface;

/**
 * PasswordEncoderInterface is the interface for all encoders.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface PasswordEncoderInterface
{
    /**
     * Encodes the raw password.
     *
     * @param string $raw  The password to encode
     * @param AccountInterface $account The salt
     *
     * @return string The encoded password
     */
    function encodePassword($raw, AccountInterface $account);

    /**
     * Checks a raw password against an encoded password.
     *
     * @param string $raw     A raw password
     * @param AccountInterface $account
     *
     * @return Boolean true if the password is valid, false otherwise
     */
    function isPasswordValid($raw, AccountInterface $account);
}
