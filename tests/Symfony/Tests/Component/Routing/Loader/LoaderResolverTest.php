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
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;

class LoaderResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Symfony\Component\Routing\Loader\LoaderResolver::__construct
     */
    public function testConstructor()
    {
        $resolver = new LoaderResolver(array(
            $loader = new ClosureLoader(),
        ));

        $this->assertEquals(array($loader), $resolver->getLoaders(), '__construct() takes an array of loaders as its first argument');
    }

    /**
     * @covers Symfony\Component\Routing\Loader\LoaderResolver::resolve
     */
    public function testResolve()
    {
        $resolver = new LoaderResolver(array(
            $loader = new ClosureLoader(),
        ));

        $this->assertFalse($resolver->resolve('foo.foo'), '->resolve() returns false if no loader is able to load the resource');
        $this->assertEquals($loader, $resolver->resolve(function () {}), '->resolve() returns the loader for the given resource');
    }

    /**
     * @covers Symfony\Component\Routing\Loader\LoaderResolver::getLoaders
     * @covers Symfony\Component\Routing\Loader\LoaderResolver::addLoader
     */
    public function testLoaders()
    {
        $resolver = new LoaderResolver();
        $resolver->addLoader($loader = new ClosureLoader());

        $this->assertEquals(array($loader), $resolver->getLoaders(), 'addLoader() adds a loader');
    }

    public function testResolveUnknownService()
    {
        $resolver = new LoaderResolver();
        $resolver->addLoader($xml = new XmlFileLoader(array()));
        $resolver->addLoader($yml = new YamlFileLoader(array()));
        $resolver->addLoader($php = new PhpFileLoader(array()));

        $this->assertSame($xml, $resolver->resolve('foo.xml'), '->resolve() finds a XML loader');
        $this->assertSame($yml, $resolver->resolve('foo.yml'), '->resolve() finds a YAML loader');
        $this->assertSame($php, $resolver->resolve('foo.php'), '->resolve() finds a PHP loader');

        $this->assertFalse($resolver->resolve('(custom) foo.xml', '->resolve() can not finds custom XML loader'));
        $this->assertFalse($resolver->resolve('(custom)foo.xml', '->resolve() can not finds custom XML loader'));
        $this->assertFalse($resolver->resolve('( custom )foo.yml', '->resolve() can not finds custom YML loader'));
        $this->assertFalse($resolver->resolve('(custom)  foo.php', '->resolve() can not finds custom PHP loader'));
    }
}
