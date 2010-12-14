<?php

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface CompilerPassInterface
{
    function process(ContainerBuilder $container);
}