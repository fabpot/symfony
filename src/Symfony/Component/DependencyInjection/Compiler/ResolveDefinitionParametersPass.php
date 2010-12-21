<?php

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Resolves place holders in the service definitions to their actual values, so
 * that following compiler passes do not need to implement this logic themself.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ResolveDefinitionParametersPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $parameterBag = $container->getParameterBag();
        foreach ($container->getDefinitions() as $definition) {
            $definition->setClass($parameterBag->resolveValue($definition->getClass()));
        }
    }
}