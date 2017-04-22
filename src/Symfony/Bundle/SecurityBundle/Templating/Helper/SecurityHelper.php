<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Templating\Helper;

use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * SecurityHelper provides read-only access to the security checker.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityHelper extends Helper
{
    private $securityChecker;

    public function __construct(AuthorizationCheckerInterface $securityChecker = null)
    {
        $this->securityChecker = $securityChecker;
    }

    public function isGranted($role, $object = null, $field = null)
    {
        if (null === $this->securityChecker) {
            return false;
        }

        if (null !== $field) {
            $object = new FieldVote($object, $field);
        }

        return $this->securityChecker->isGranted($role, $object);
    }
    
    public function getUser()
    {
        if (!$this->context) {
            return;
        }

        if (!$token = $this->context->getToken()) {
            return;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'security';
    }
}
