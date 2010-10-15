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
            $loader = new XmlLoader(__DIR__ . '../Resources/config');
            $loader->load($this->resources['config']);
        }
    }
}
