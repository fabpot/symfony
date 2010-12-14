<?php

namespace Symfony\Component\DependencyInjection\Compiler;

class ResolveInterfaceInjectorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            foreach ($container->getInterfaceInjectors() as $injector) {
                if (null !== $definition->getFactoryService()) {
                    continue;
                }
                $defClass = $container->parameterBag->resolveValue($definition->getClass());
                $definition->setClass($defClass);
                if ($injector->supports($defClass)) {
                    $injector->processDefinition($definition);
                }
            }
        }
    }
}