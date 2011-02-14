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
use Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser;
use Symfony\Bundle\FrameworkBundle\Tests\Kernel;
use Symfony\Bundle\FrameworkBundle\Templating\Template;

class TemplateNameParserTest extends TestCase
{
    protected $parser;

    protected function  setUp()
    {
        $kernel = new Kernel();
        $kernel->boot();

        $this->parser = new TemplateNameParser($kernel);
    }

    protected function tearDown()
    {
        unset($this->parser);
    }

    /**
     * @dataProvider getLogicalNameToTemplateProvider
     */
    public function testParse($name, $ref)
    {
        $template = $this->parser->parse($name);

        $this->assertEquals($template->getSignature(), $ref->getSignature());
                
    }

    public function getLogicalNameToTemplateProvider()
    {
        return array(
            array('FooBundle:Post:index.html.php', new Template('FooBundle', 'Post', 'index', 'html', 'php')),
            array('FooBundle:Post:index.html.twig', new Template('FooBundle', 'Post', 'index', 'html', 'twig')),
            array('FooBundle:Post:index.xml.php', new Template('FooBundle', 'Post', 'index', 'xml', 'php')),
            array('SensioFooBundle:Post:index.html.php', new Template('SensioFooBundle', 'Post', 'index', 'html', 'php')),
            array('SensioCmsFooBundle:Post:index.html.php', new Template('SensioCmsFooBundle', 'Post', 'index', 'html', 'php')),
            array(':Post:index.html.php', new Template('', 'Post', 'index', 'html', 'php')),
            array('::index.html.php', new Template('', '', 'index', 'html', 'php')),
        );
    }

    /**
     * @dataProvider      getInvalidLogicalNameProvider
     * @expectedException \InvalidArgumentException
     */
    public function testParseInvalidName($name)
    {
        $this->parser->parse($name);
    }

    public function getInvalidLogicalNameProvider()
    {
        return array(
            array('BarBundle:Post:index.html.php'),
            array('FooBundle:Post:index'),
            array('FooBundle:Post'),
            array('FooBundle:Post:foo:bar'),
            array('FooBundle:Post:index.foo.bar.foobar'),
        );
    }

    /**
     * @dataProvider getFilenameToTemplateProvider
     */
    public function testParseFromFilename($file, $ref)
    {
        $template = $this->parser->parseFromFilename($file);
        
        if ($ref === false) {
            $this->assertFalse($template);
        } else {
            $this->assertEquals($template->getSignature(), $ref->getSignature());
        }
    }

    public function getFilenameToTemplateProvider()
    {
        return array(
            array('/path/to/section/name.format.engine', new Template('', '/path/to/section', 'name', 'format', 'engine')),
            array('\\path\\to\\section\\name.format.engine', new Template('', '/path/to/section', 'name', 'format', 'engine')),
            array('name.format.engine', new Template('', '', 'name', 'format', 'engine')),
            array('name.format', false),
            array('name', false),
        );
    }

}
