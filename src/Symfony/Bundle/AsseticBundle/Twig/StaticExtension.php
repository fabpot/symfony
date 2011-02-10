<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Twig;

use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Factory\AssetFactory;

/**
 * Assetic integration.
 *
 * @author Kris Wallsmith <kris.wallsmith@symfony-project.com>
 */
class StaticExtension extends AsseticExtension
{
    static protected function createTokenParser(AssetFactory $factory, $debug = false)
    {
        return new StaticTokenParser($factory, $debug);
    }
}
