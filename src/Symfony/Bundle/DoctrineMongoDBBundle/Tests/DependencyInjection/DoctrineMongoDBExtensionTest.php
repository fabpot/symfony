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
     */
    public function testOptionMerge($inputOption, $endOption, $value)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $loader = new DoctrineMongoDBExtensionStub();
        $loader->mongodbLoad(array(array($inputOption => $value)), $container);

        $options = $loader->getMongodbOptions();
        $this->assertEquals($options[$endOption], $value);
    }

    public function optionProvider()
    {
        return array(
            array('default_document_manager', 'default_document_manager', 'foo'),
            array('default-document-manager', 'default_document_manager', 'bar'),
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