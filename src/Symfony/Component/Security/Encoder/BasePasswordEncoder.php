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

/**
 * BasePasswordEncoder is the base class for all password encoders.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class BasePasswordEncoder implements PasswordEncoderInterface
{
    /**
     * Demerges a merge password and salt string.
     *
     * @param string $mergedPasswordSalt The merged password and salt string
     *
     * @return array An array where the first element is the password and the second the salt
     *
     * @throws \InvalidArgumentException When the merged password and salt use an invalid format
     */
    protected function demergePasswordAndSalt($mergedPasswordSalt)
    {
        $level = error_reporting(0);
        $passwordSalt = unserialize($mergedPasswordSalt);
        error_reporting($level);

        if ($passwordSalt === false) {
            throw new \InvalidArgumentException('Invalid format used for merged password and salt.');
        }

        return $passwordSalt;
    }

    /**
     * Merges a password and a salt.
     *
     * @param string $password the password to be used
     * @param string $salt the salt to be used
     *
     * @return string a merged password and salt
     */
    protected function mergePasswordAndSalt($password, $salt)
    {
        return serialize(array($password, $salt));
    }

    /**
     * Compares two passwords.
     *
     * This method implements a constant-time algorithm to compare
     * passwords to avoid (remote) timing attacks.
     *
     * @param string $password1 The first password
     * @param string $password2 The second password
     *
     * @return Boolean true if the two passwords are the same, false otherwise
     */
    protected function comparePasswords($password1, $password2)
    {
        if (strlen($password1) !== strlen($password2)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($password1); $i++) {
            $result |= ord($password1[$i]) ^ ord($password2[$i]);
        }

        return 0 === $result;
    }
}
