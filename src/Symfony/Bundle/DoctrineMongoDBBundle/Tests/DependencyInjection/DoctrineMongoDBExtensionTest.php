<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;

class DoctrineMongoDBExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidOptionThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = new ContainerBuilder();
        $loader = new DoctrineMongoDBExtension();
        $loader->mongodbLoad(array(array('pretend_config' => 'bar')), $container);
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testParameterOverride($option, $parameter, $value)
    {
        $container = new ContainerBuilder();
        $loader = new DoctrineMongoDBExtension();
        $loader->mongodbLoad(array(array($option => $value)), $container);

        $this->assertEquals($value, $container->getParameter('doctrine.odm.mongodb.'.$parameter));
    }

    public function parameterProvider()
    {
        return array(
            array('default_database', 'default_database', 'foo'),
            array('default-database', 'default_database', 'bar'),
        );
    }

    /**
     * @dataProvider optionProvider
     * @param array $configs The source array of configuration arrays
     * @param array $correctValues A key-value pair of end values to check
     */
    public function testMergeOptions(array $configs, array $correctValues)
    {
        $loader = new DoctrineMongoDBExtensionStub();

        $options = $loader->mergeConfigs($configs);
        foreach ($correctValues as $key => $correctVal)
        {
            $this->assertEquals($correctVal, $options[$key]);
        }
    }

    public function optionProvider()
    {
        $cases = array();

        // single config, testing normal option setting
        $cases[] = array(
            array(
                array('default_document_manager' => 'foo'),
            ),
            array('default_document_manager' => 'foo')
        );

        // single config, testing normal option setting with dashes
        $cases[] = array(
            array(
                array('default-document-manager' => 'bar'),
            ),
            array('default_document_manager' => 'bar')
        );

        // testing the normal override merging - the later config array wins
        $cases[] = array(
            array(
                array('default_document_manager' => 'foo'),
                array('default_document_manager' => 'baz'),
            ),
            array('default_document_manager' => 'baz')
        );

        // the "options" array is totally replaced
        $cases[] = array(
            array(
                array('options' => array('lorem' => 'ipsum')),
                array('options' => array('foo' => 'bar')),
            ),
            array('options' => array('foo' => 'bar')),
        );

        // mappings are merged non-recursively. No item validation takes place.
        $cases[] = array(
            array(
                array('mappings' => array('foo' => array('opt1' => 'val1'), 'bar' => array('opt2' => 'val2'))),
                array('mappings' => array('bar' => array('opt3' => 'val3'))),
            ),
            array('mappings' => array('foo' => array('opt1' => 'val1'), 'bar' => array('opt3' => 'val3'))),
        );

        // connections are merged non-recursively. No item validation takes place.
        $cases[] = array(
            array(
                array('connections' => array('foo' => array('opt1' => 'val1'), 'bar' => array('opt2' => 'val2'))),
                array('connections' => array('bar' => array('opt3' => 'val3'))),
            ),
            array('connections' => array('foo' => array('opt1' => 'val1'), 'bar' => array('opt3' => 'val3'))),
        );

        // managers are merged non-recursively. No item validation takes place.
        $cases[] = array(
            array(
                array('document_managers' => array('foo' => array('opt1' => 'val1'), 'bar' => array('opt2' => 'val2'))),
                array('document_managers' => array('bar' => array('opt3' => 'val3'))),
            ),
            array('document_managers' => array('foo' => array('opt1' => 'val1'), 'bar' => array('opt3' => 'val3'))),
        );

        return $cases;
    }

    /**
     * @dataProvider getNormalizationTests
     */
    public function testNormalizeOptions(array $config, $targetKey, array $normalized)
    {
        $loader = new DoctrineMongoDBExtensionStub();

        $options = $loader->mergeConfigs(array($config));
        $this->assertSame($normalized, $options[$targetKey]);
    }

    public function getNormalizationTests()
    {
        return array(
            // connection versus connections (id is the identifier)
            array(
                array('connection' => array(
                    array('server' => 'mongodb://abc', 'id' => 'foo'),
                    array('server' => 'mongodb://def', 'id' => 'bar'),
                )),
                'connections',
                array(
                    'foo' => array('server' => 'mongodb://abc'),
                    'bar' => array('server' => 'mongodb://def'),
                ),
            ),
            // document_manager versus document_managers (id is the identifier)
            array(
                array('document_manager' => array(
                    array('connection' => 'conn1', 'id' => 'foo'),
                    array('connection' => 'conn2', 'id' => 'bar'),
                )),
                'document_managers',
                array(
                    'foo' => array('connection' => 'conn1'),
                    'bar' => array('connection' => 'conn2'),
                ),
            ),
            // mapping versus mappings (name is the identifier)
            array(
                array('mapping' => array(
                    array('type' => 'yml', 'name' => 'foo'),
                    array('type' => 'xml', 'name' => 'bar'),
                )),
                'mappings',
                array(
                    'foo' => array('type' => 'yml'),
                    'bar' => array('type' => 'xml'),
                ),
            ),
            // mapping configuration that's beneath a specific document manager
            array(
                array('document_manager' => array(
                    array('id' => 'foo', 'connection' => 'conn1', 'mapping' => array(
                        'type' => 'xml', 'name' => 'foo-mapping'
                    )),
                )),
                'document_managers',
                array(
                    'foo' => array('connection' => 'conn1', 'mappings' => array(
                        'foo-mapping' => array('type' => 'xml'),
                    )),
                ),
            ),
        );
    }
}

class DoctrineMongoDBExtensionStub extends DoctrineMongoDBExtension
{
    public function getMongodbOptions()
    {
        return $this->mongodbOptions;
    }
}