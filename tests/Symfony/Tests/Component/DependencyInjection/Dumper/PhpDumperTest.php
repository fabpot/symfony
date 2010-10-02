<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\DependencyInjection\Dumper;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\InterfaceInjector;

class PhpDumperTest extends \PHPUnit_Framework_TestCase
{
    static protected $fixturesPath;

    static public function setUpBeforeClass()
    {
        self::$fixturesPath = realpath(__DIR__.'/../Fixtures/');
    }

    public function testDump()
    {
        $dumper = new PhpDumper($container = new ContainerBuilder());

        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services1.php', $dumper->dump(), '->dump() dumps an empty container as an empty PHP class');
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services1-1.php', $dumper->dump(array('class' => 'Container', 'base_class' => 'AbstractContainer')), '->dump() takes a class and a base_class options');

        $container = new ContainerBuilder();
        $dumper = new PhpDumper($container);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExportParameters()
    {
        $dumper = new PhpDumper($container = new ContainerBuilder(new ParameterBag(array('foo' => new Reference('foo')))));
        $dumper->dump();
    }

    public function testAddParameters()
    {
        $container = include self::$fixturesPath.'/containers/container8.php';
        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services8.php', $dumper->dump(), '->dump() dumps parameters');
    }

    public function testAddService()
    {
        $container = include self::$fixturesPath.'/containers/container9.php';
        $dumper = new PhpDumper($container);
        $this->assertEquals(str_replace('%path%', str_replace('\\','\\\\',self::$fixturesPath.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR), file_get_contents(self::$fixturesPath.'/php/services9.php')), $dumper->dump(), '->dump() dumps services');

        $dumper = new PhpDumper($container = new ContainerBuilder());
        $container->register('foo', 'FooClass')->addArgument(new \stdClass());
        try {
            $dumper->dump();
            $this->fail('->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\RuntimeException', $e, '->dump() returns a LogicException if the dump() method has not been overriden by a children class');
            $this->assertEquals('Unable to dump a service container if a parameter is an object or a resource.', $e->getMessage(), '->dump() returns a LogicException if the dump() method has not been overriden by a children class');
        }
    }

    public function testOverrideServiceWhenUsingADumpedContainer()
    {
        require_once self::$fixturesPath.'/php/services9.php';
        require_once self::$fixturesPath.'/includes/foo.php';

        $container = new \ProjectServiceContainer();
        $container->set('bar', $bar = new \stdClass());
        $container->setParameter('foo_bar', 'foo_bar');

        $this->assertEquals($bar, $container->getBarService(), '->set() overrides an already defined service');
        $this->assertEquals($bar, $container->get('bar'), '->set() overrides an already defined service');
    }

    public function testInterfaceInjectors()
    {
        $interfaceInjector = new InterfaceInjector('FooClass');
        $interfaceInjector->addMethodCall('setBar', array('someValue'));
        $container = include self::$fixturesPath.'/containers/interfaces1.php';
        $container->addInterfaceInjector($interfaceInjector);

        $dumper = new PhpDumper($container);

        $classBody = $dumper->dump();
        // TODO: need a proper way of testing it
        // var_dump($classBody);
        $this->assertEquals("<?php

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * ProjectServiceContainer
 *
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 */
class ProjectServiceContainer extends Container
{
    protected \$shared = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(new ParameterBag(\$this->getDefaultParameters()));
    }

    /**
     * Gets the 'foo' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Object A %cla%o%ss% instance.
     */
    protected function getFooService()
    {
        if (isset(\$this->shared['foo'])) return \$this->shared['foo'];

        \$class = \$this->getParameter('cla').'o'.\$this->getParameter('ss');
        \$instance = new \$class();
        \$this->shared['foo'] = \$instance;
        \$instance->setBar('someValue');

        return \$instance;
    }

    /**
     * Returns service ids for a given tag.
     *
     * @param string \$name The tag name
     *
     * @return array An array of tags
     */
    public function findTaggedServiceIds(\$name)
    {
        static \$tags = array (
);

        return isset(\$tags[\$name]) ? \$tags[\$name] : array();
    }

    /**
     * Gets the default parameters.
     *
     * @return array An array of the default parameters
     */
    protected function getDefaultParameters()
    {
        return array(
            'cla' => 'Fo',
            'ss' => 'Class',
        );
    }
}
", $classBody);
    }
}
