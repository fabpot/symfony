<?php

namespace Symfony\Bundle\DoctrineMigrationsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * DoctrineMigrations dependency injection extension
 *
 * @author Henrik Bjornskov <henrik@kaffekoder.dk>
 */
class DoctrineMigrationsExtension extends Extension
{
    protected $resources = array(
        'config' => 'config.xml',
    );

    /**
     * Loads the default config file for registering commands
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    public function configLoad($config, ContainerBuilder $container)
    {
        if (!$container->hasDefinition('config')) {
            $loader = new XmlFileLoader($container, __DIR__ . '/../Resources/config');
            $loader->load($this->resources['config']);
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return null;
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/doctrine-migrations';
    }

    /**
     * Returns the name used for this service `doctrine_migrations.config: ~`
     * 
     * @return string
     */
    public function getAlias()
    {
        return 'doctrine_migrations';
    }
}
