<?php

namespace Symfony\Component\DependencyInjection\Compiler;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface that must be implemented by passes that are run as part of an
 * RepeatedPass.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface RepeatablePassInterface extends CompilerPassInterface
{
    function setRepeatedPass(RepeatedPass $repeatedPass);
}