<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Routing\Loader;

use Symfony\Component\Routing\Loader\LoaderResolver;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AnnotationClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Symfony\Component\Routing\Loader\AnnotationClassLoader::supports
     */
    public function testSupports()
    {
        $loader = $this->getMockBuilder('Symfony\Component\Routing\Loader\AnnotationClassLoader')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertTrue($loader->supports('\stdClass'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');

        $this->assertTrue($loader->supports('\stdClass', 'annotation'), '->supports() checks the resource type if specified');
        $this->assertFalse($loader->supports('\stdClass', 'foo'), '->supports() checks the resource type if specified');
    }
}
