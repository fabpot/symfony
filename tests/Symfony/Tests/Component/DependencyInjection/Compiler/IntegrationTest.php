<?php

namespace Symfony\Tests\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This class tests the integration of the different compiler passes
 */
class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * This tests that the following dependencies are correctly processed:
     *
     * A is public, B/C are private
     * A -> C
     * B -> C
     */
    public function testProcessRemovesAndInlinesRecursively()
    {
        $container = new ContainerBuilder();

        $a = $container
            ->register('a')
            ->addArgument(new Reference('c'))
        ;

        $b = $container
            ->register('b')
            ->addArgument(new Reference('c'))
            ->setPublic(false)
        ;

        $c = $container
            ->register('c')
            ->setPublic(false)
        ;

        $container->freeze();

        $this->assertTrue($container->hasDefinition('a'));
        $arguments = $a->getArguments();
        $this->assertSame($c, $arguments[0]);
        $this->assertFalse($container->hasDefinition('b'));
        $this->assertFalse($container->hasDefinition('c'));
    }

    public function testProcessInlinesReferencesToAliases()
    {
        $container = new ContainerBuilder();

        $a = $container
            ->register('a')
            ->addArgument(new Reference('b'))
        ;

        $container->setAlias('b', new Alias('c', false));

        $c = $container
            ->register('c')
            ->setPublic(false)
        ;

        $container->freeze();

        $this->assertTrue($container->hasDefinition('a'));
        $arguments = $a->getArguments();
        $this->assertSame($c, $arguments[0]);
        $this->assertFalse($container->hasAlias('b'));
        $this->assertFalse($container->hasDefinition('c'));
    }

    public function testProcessInlinesWhenThereAreMultipleReferencesButFromTheSameDefinition()
    {
        $container = new ContainerBuilder();

        $container
            ->register('a')
            ->addArgument(new Reference('b'))
            ->addMethodCall('setC', array(new Reference('c')))
        ;

        $container
            ->register('b')
            ->addArgument(new Reference('c'))
            ->setPublic(false)
        ;

        $container
            ->register('c')
            ->setPublic(false)
        ;

        $container->freeze();

        $this->assertTrue($container->hasDefinition('a'));
        $this->assertFalse($container->hasDefinition('b'));
        $this->assertFalse($container->hasDefinition('c'), 'Service C was not inlined.');
    }
}