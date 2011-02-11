<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Templating;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Templating\Template;

class TemplateNameParserTest extends TestCase
{
    protected $parser;

    protected function  setUp()
    {
        $this->parser = $this
            ->getMockBuilder('Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser')
            ->setMethods(array('createTemplate'))
            ->disableOriginalConstructor()
            ->getMock()
        ;

    }

    protected function tearDown()
    {
        unset($this->parser);
    }

    /**
     * @dataProvider getParseTests
     */
    public function testParse($name, $params)
    {
        $this->parser
            ->expects($this->once())
            ->method('createTemplate')
            ->with($params[0], $params[1], $params[2], $params[3], $params[4])
            ->will($this->returnValue(new Template()))
        ;

        $this->parser->parse($name);
    }

    public function getParseTests()
    {
        return array(
            array('FooBundle:Post:index.html.php', array('FooBundle', 'Post', 'index', 'html', 'php')),
            array('FooBundle:Post:index.html.twig', array('FooBundle', 'Post', 'index', 'html', 'twig')),
            array('FooBundle:Post:index.xml.php', array('FooBundle', 'Post', 'index', 'xml', 'php')),
            array('SensioFooBundle:Post:index.html.php', array('SensioFooBundle', 'Post', 'index', 'html', 'php')),
            array('SensioCmsFooBundle:Post:index.html.php', array('SensioCmsFooBundle', 'Post', 'index', 'html', 'php')),
            array(':Post:index.html.php', array('', 'Post', 'index', 'html', 'php')),
            array('::index.html.php', array('', '', 'index', 'html', 'php')),
        );
    }

    /**
     * @dataProvider      getParseInvalidTests
     * @expectedException \InvalidArgumentException
     */
    public function testParseInvalid($name)
    {
        $this->parser
            ->expects($this->any())
            ->method('createTemplate')
            ->will($this->returnValue(new Template()))
        ;

        $this->parser->parse($name);

    }

    public function getParseInvalidTests()
    {
        return array(
            array('BarBundle:Post:index.html.php'),
            array('FooBundle:Post:index'),
            array('FooBundle:Post'),
            array('FooBundle:Post:foo:bar'),
            array('FooBundle:Post:index.foo.bar.foobar'),
        );
    }

}
