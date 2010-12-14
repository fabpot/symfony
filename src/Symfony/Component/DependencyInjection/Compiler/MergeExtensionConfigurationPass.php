<?php

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class MergeExtensionConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $parameters = $container->parameterBag->all();
        $definitions = $container->definitions;
        $aliases = $container->aliases;

        foreach ($container->getExtensionConfigs() as $name => $configs) {
            list($namespace, $tag) = explode(':', $name);

            $extension = $container->getExtension($namespace);

            $tmpContainer = new ContainerBuilder($container->parameterBag);
            $tmpContainer->addObjectResource($extension);
            foreach ($configs as $config) {
                $extension->load($tag, $config, $tmpContainer);
            }

            $container->merge($tmpContainer);
        }

        $container->setExtensionConfigs(array());
        $container->addDefinitions($definitions);
        $container->addAliases($aliases);
        $container->parameterBag->add($parameters);
    }
}