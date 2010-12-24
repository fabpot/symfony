<?php

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Adds tagged request.param_converter to request.param_converter.manager service
 *
 * @author Henrik Bjornskov <hb@peytz.dk>
 */
class ConverterManagerPass implements CompilerPassInterface
{
    /**
     * Adds ParamConverters to ConverterManager
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('request.param_converter.manager')) {
            return;
        }

        $definition = $container->getDefinition('request.param_converter.manager');

        foreach ($container->findTaggedServiceIds('request.param_converter') as $serviceId => $attributes) {
            $priority = 0;
            if (isset($attributes['priority'])) {
                $priority = (integer) $attributes['priority'];
            }

            $definition->addMethodCall('add', array(new Reference($serviceId), $priority));
        }
    }
}
