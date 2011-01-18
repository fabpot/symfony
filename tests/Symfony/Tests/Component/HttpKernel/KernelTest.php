<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpKernel;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\DependencyInjection\Loader\LoaderInterface;

class KernelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSafeName()
    {
        $kernel = new KernelForTest('dev', true, '-foo-');

        $this->assertEquals('foo', $kernel->getSafeName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLocateResourceThrowsExceptionWhenNameIsNotValid()
    {
        $this->getKernelForInvalidLocateResource()->locateResource('foo');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLocateResourceThrowsExceptionWhenNameIsUnsafe()
    {
        $this->getKernelForInvalidLocateResource()->locateResource('@foo/../bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLocateResourceThrowsExceptionWhenBundleDoesNotExist()
    {
        $this->getKernelForInvalidLocateResource()->locateResource('@foo/config/routing.xml');
    }

    public function testLocateResourceReturnsTheFirstThatMatches()
    {
        $kernel = $this->getKernelForLocateResource();
        $kernel
            ->expects($this->once())
            ->method('getBundle')
            ->will($this->returnValue($this->getBundle(__DIR__.'/Fixtures/Bundle1')))
        ;

        $this->assertEquals(__DIR__.'/Fixtures/Bundle1/foo.txt', $kernel->locateResource('@foo/foo.txt'));
    }

    public function testLocateResourceReturnsTheAllMatches()
    {
        $kernel = $this->getKernelForLocateResource();
        $kernel
            ->expects($this->once())
            ->method('getBundle')
            ->with($this->anything(), $this->equalTo(false))
            ->will($this->returnValue(array($this->getBundle(__DIR__.'/Fixtures/Bundle1'), $this->getBundle(__DIR__.'/Fixtures/Bundle2'))))
        ;

        $this->assertEquals(array(__DIR__.'/Fixtures/Bundle1/foo.txt', __DIR__.'/Fixtures/Bundle2/foo.txt'), $kernel->locateResource('@foo/foo.txt', null, false));
    }

    public function testLocateResourceReturnsAllMatchesBis()
    {
        $kernel = $this->getKernelForLocateResource();
        $kernel
            ->expects($this->once())
            ->method('getBundle')
            ->with($this->anything(), $this->equalTo(false))
            ->will($this->returnValue(array($this->getBundle(__DIR__.'/Fixtures/Bundle1'), $this->getBundle(__DIR__.'/foobar'))))
        ;

        $this->assertEquals(array(__DIR__.'/Fixtures/Bundle1/foo.txt'), $kernel->locateResource('@foo/foo.txt', null, false));
    }

    public function testLocateResourceIgnoresDirOnNonResource()
    {
        $kernel = $this->getKernelForLocateResource();
        $kernel
            ->expects($this->once())
            ->method('getBundle')
            ->will($this->returnValue($this->getBundle(__DIR__.'/Fixtures/Bundle1')))
        ;

        $this->assertEquals(__DIR__.'/Fixtures/Bundle1/foo.txt', $kernel->locateResource('@foo/foo.txt', __DIR__.'/Fixtures'));
    }

    public function testLocateResourceReturnsTheDirOneForResources()
    {
        $kernel = $this->getKernelForLocateResource();

        $this->assertEquals(__DIR__.'/Fixtures/foo/foo.txt', $kernel->locateResource('@foo/Resources/foo.txt', __DIR__.'/Fixtures'));
    }

    public function testLocateResourceReturnsTheDirOneForResourcesAndBundleOnes()
    {
        $kernel = $this->getKernelForLocateResource();
        $kernel
            ->expects($this->once())
            ->method('getBundle')
            ->will($this->returnValue(array($this->getBundle(__DIR__.'/Fixtures/Bundle1'))))
        ;

        $this->assertEquals(array(__DIR__.'/Fixtures/foo/foo.txt', __DIR__.'/Fixtures/Bundle1/Resources/foo.txt'), $kernel->locateResource('@foo/Resources/foo.txt', __DIR__.'/Fixtures', false));
    }

    protected function getBundle($dir)
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle
            ->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($dir))
        ;

        return $bundle;
    }

    protected function getKernelForInvalidLocateResource()
    {
        return $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
    }

    protected function getKernelForLocateResource()
    {
        return $this
            ->getMockBuilder('Symfony\Tests\Component\HttpKernel\KernelForTest')
            ->setMethods(array('getBundle'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}

class KernelForTest extends Kernel
{
    public function __construct($environment, $debug, $name)
    {
        parent::__construct($environment, $debug);

        $this->name = $name;
    }

    public function registerRootDir()
    {
    }

    public function registerBundles()
    {
    }

    public function registerBundleDirs()
    {
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}