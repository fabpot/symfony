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

        $options = $loader->mergeOptions($configs);
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

        return $cases;
    }
}

class DoctrineMongoDBExtensionStub extends DoctrineMongoDBExtension
{
    public function getMongodbOptions()
    {
        return $this->mongodbOptions;
    }
}