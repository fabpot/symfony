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
 * PlaintextPasswordEncoder does not do any encoding.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
use Symfony\Component\Security\User\AccountInterface;

class PlaintextPasswordEncoder extends BasePasswordEncoder
{
    protected $ignorePasswordCase;

    public function __construct($ignorePasswordCase = false)
    {
        $this->ignorePasswordCase = $ignorePasswordCase;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, AccountInterface $account)
    {
        return $this->mergePasswordAndSalt($raw, $account->getSalt());
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($raw, AccountInterface $account)
    {
        $pass2 = $this->mergePasswordAndSalt($raw, $account->getSalt());

        if (!$this->ignorePasswordCase) {
            return $this->comparePasswords($account->getPassword(), $pass2);
        } else {
            return $this->comparePasswords(strtolower($account->getPassword()), strtolower($pass2));
        }
    }
}
