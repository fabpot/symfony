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

use Symfony\Bundle\FrameworkBundle\HttpFoundation\Session;

/**
 * SessionStub
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class SessionStub extends Session
{
    public $saved = false;

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\HttpFoundation\Session::save()
     */
    public function save()
    {
        $this->saved = true;
    }
}
