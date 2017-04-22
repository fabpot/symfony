<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\Form;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\MongoDB\Connection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DoctrineMongoDBOdmTestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Doctrine\\Common\\Version')) {
            $this->markTestSkipped('Doctrine is not available.');
        }
    }

    /**
     * @return DocumentManager
     */
    protected function createTestDocumentManager($paths = array())
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine\ODM\MongoDB');
        $config->setHydratorDir(\sys_get_temp_dir());
        $config->setHydratorNamespace('SymfonyTests\Doctrine\ODM\MongoDB');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths));
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $conn = new Connection();
        return DocumentManager::create($conn, $config);
    }
}
