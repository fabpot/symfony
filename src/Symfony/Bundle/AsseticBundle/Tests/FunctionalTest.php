<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests;

use Symfony\Bundle\AsseticBundle\Tests\Kernel\TestKernel;
use Symfony\Component\HttpFoundation\Request;

class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $cache = __DIR__.'/Kernel/cache';
        if (!is_dir($cache)) {
            mkdir($cache);
        } else {
            shell_exec('rm -rf '.escapeshellarg(__DIR__.'/Kernel/cache/*'));
        }
    }

    protected function tearDown()
    {
        shell_exec('rm -rf '.escapeshellarg(__DIR__.'/Kernel/cache'));
    }

    /**
     * @dataProvider provideDebugAndAssetCount
     */
    public function testKernel($debug, $count)
    {
        $kernel = new TestKernel('test', $debug);
        $kernel->boot();
        $container = $kernel->getContainer();
        $container->get('cache_warmer')->warmUp($container->getParameter('kernel.cache_dir'));

        $assets = $container->get('assetic.asset_manager')->all();

        $this->assertEquals($count, count($assets));
    }

    /**
     * @dataProvider provideDebugAndAssetCount
     */
    public function testRoutes($debug, $count)
    {
        $kernel = new TestKernel('test', $debug);
        $kernel->boot();
        $container = $kernel->getContainer();
        $container->get('cache_warmer')->warmUp($container->getParameter('kernel.cache_dir'));

        $routes = $container->get('router')->getRouteCollection()->all();

        $matches = 0;
        foreach (array_keys($routes) as $name) {
            if (0 === strpos($name, 'assetic_')) {
                ++$matches;
            }
        }

        $this->assertEquals($count, $matches);
    }

    public function testRenderDebug()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        $container->enterScope('request');
        $container->set('request', new Request());
        $container->get('cache_warmer')->warmUp($container->getParameter('kernel.cache_dir'));

        $content = $container->get('templating')->render('::layout.html.twig');

        $this->assertEquals(2, substr_count($content, '<!-- foo -->'));
    }

    public function provideDebugAndAssetCount()
    {
        return array(
            array(true, 2),
            array(false, 1),
        );
    }
}
