<?php

namespace Symfony\Bundle\TwigBundle\Templating;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * HelperInterface is the interface implemented by all Templating\Helper\HelperInterface classes.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface HelperInterface
{
    /**
     * Creates a Twig token parser
     *
     * @return array of Twig_TokenParser instance that describe how to call this helper
     */
    public function getTwigTokenParsers();
}
