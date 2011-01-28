<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\FileLocator;
use Symfony\Bundle\DoctrineAbstractBundle\DependencyInjection\AbstractDoctrineExtension;

/**
 * Doctrine MongoDB ODM extension.
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 * @author Kris Wallsmith <kris.wallsmith@symfony-project.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineMongoDBExtension extends AbstractDoctrineExtension
{
    /**
     * Responds to the doctrine_odm.mongodb configuration parameter.
     * 
     * Available options:
     *
     *  * mappings                  An array of bundle names (as the key)
     *                              and mapping configuration (as the value).
     *  * default_document_manager  The name of the document manager that should be
     *                              marked as the default. Default: default.
     *  * default_connection        If using a single connection, the name to give
     *                              to that connection. Default: default.
     *  * metadata_cache_driver     Options: array (default), apc, memcache, xcache
     *  * server                    The server if only specifying one connection
     *                              (e.g. mongodb://localhost:27017)
     *  * options                   The connections options if only specifying
     *                              one connection.
     *  * connections               An array of each connection and its configuration
     *  * document_managers         An array of document manager names and
     *                              configuration.
     *  * default_database          The database for a document manager that didn't
     *                              explicitly set a database. Default: default;
     *  * proxy_namespace           Namespace of the generated proxies. Default: Proxies
     *  * auto_generate_proxy_classes Whether to always regenerate the proxt classes.
     *                              Default: false.
     *  * hydrator_namespace        Namespace of the generated proxies. Default: Hydrators
     *  * auto_generate_hydrator_classes Whether to always regenerate the proxt classes.
     *                              Default: false.
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Load DoctrineMongoDBBundle/Resources/config/mongodb.xml
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('mongodb.xml');

        // merge all the configs into one options array
        $options = $this->mergeOptions($configs);

        // set some options as parameters and unset them
        $options = $this->overrideParameters($options, $container);
        $this->loadConnections($options, $container);
        $this->loadDocumentManagers($options, $container);
        $this->loadConstraints($options, $container);
    }

    /**
     * Merges a set of configuration arrays and returns the result.
     *
     * This method internally specifies the available options and their
     * default values. Given an array of configuration arrays, this method
     * intelligently merges those configuration values and returns the final,
     * flattened product.
     *
     * The top-level of the returned array is normalized to turn all dashes
     * (-) into underscores (_). The input configuration keys can use either
     * format.
     *
     * @param array $configs An array of option arrays that will be merged in
     * @return The flattened, normalized options array
     * @throws InvalidArgumentException When an unrecognized option is found
     */
    public function mergeOptions(array $configs)
    {
        // The default and available options. These are very specific to this method.
        $options = array(
            'mappings'                          => array(),
            'default_document_manager'          => 'default',
            'default_connection'                => 'default',
            'metadata_cache_driver'             => 'array',
            'server'                            => null,
            'options'                           => array(),
            'connections'                       => array(),
            'document_managers'                 => array(),
            'default_database'                  => null,
            'proxy_namespace'                   => null,
            'auto_generate_proxy_classes'       => null,
            'hydrator_namespace'                => null,
            'auto_generate_hydrator_classes'    => null,
        );

        foreach ($configs as $config) {

            $config = self::normalizeKeys($config);
            // loop through each available option, look for it on the config array
            foreach ($options as $key => $val) {
                if (array_key_exists($key, $config)) {
                    $options[$key] = $this->processOptionMerge($key, $val, $config[$key]);
                    unset($config[$key]);
                }
            }

            if (count($config) > 0) {
                throw new \InvalidArgumentException('The following options are not supported: '.implode(', ', $config));
            }
        }

        return $options;
    }

    /**
     * Merges an existing and new option value together for a given option.
     *
     * This method can be written to perform complex merging for specific
     * options and simple merging for other options.
     *
     * @param  string $option   The name of the option that needs to be merged
     * @param  mixed $current   The value of the option before the merge
     * @param  mixed $new       The new value that needs to be merged in
     * @return mixed The merged value
     */
    protected function processOptionMerge($option, $current, $new)
    {
        // process the merge of the config value ($newVal)
        switch ($option)
        {
            default:
                // just override the previous value with the new one
                return $new;
        }
    }

    /**
     * Uses some of the extension options to override DI extension parameters.
     *
     * @param array $options The available configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function overrideParameters($options, ContainerBuilder $container)
    {
        $overrides = array(
            'default_database',
            'proxy_namespace',
            'auto_generate_proxy_classes',
            'hydrator_namespace',
            'auto_generate_hydrator_classes',
        );

        foreach ($overrides as $key) {
            if (isset($options[$key])) {
                $container->setParameter('doctrine.odm.mongodb.'.$key, $options[$key]);

                // the option should not be used, the parameter should be referenced
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * Loads the document managers configuration.
     *
     * @param array $options An array of extension options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManagers(array $options, ContainerBuilder $container)
    {
        $documentManagers = $this->getDocumentManagers($options, $container);
        foreach ($documentManagers as $name => $documentManager) {
            $documentManager['name'] = $name;
            $this->loadDocumentManager(
                $options['default_document_manager'],
                $options['metadata_cache_driver'],
                $documentManager,
                $container
            );
        }
        $container->setParameter('doctrine.odm.mongodb.document_managers', array_keys($documentManagers));
    }

    /**
     * Loads a document manager configuration.
     *
     * @param string $defaultManagerName  The name of the default manager
     * @param string $defaultMetadataCacheDriver The name of the default metadata cache driver
     * @param array $documentManager      A document manager configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManager($defaultManagerName, $defaultMetadataCacheDriver, array $documentManager, ContainerBuilder $container)
    {
        $defaultDatabase = isset($documentManager['default_database']) ? $documentManager['default_database'] : '%doctrine.odm.mongodb.default_database%';
        $configServiceName = sprintf('doctrine.odm.mongodb.%s_configuration', $documentManager['name']);

        if ($container->hasDefinition($configServiceName)) {
            $odmConfigDef = $container->getDefinition($configServiceName);
        } else {
            $odmConfigDef = new Definition('%doctrine.odm.mongodb.configuration_class%');
            $container->setDefinition($configServiceName, $odmConfigDef);
        }

        $this->loadDocumentManagerBundlesMappingInformation($documentManager, $odmConfigDef, $container);
        $this->loadDocumentManagerMetadataCacheDriver($defaultMetadataCacheDriver, $documentManager, $container);

        $methods = array(
            'setMetadataCacheImpl' => new Reference(sprintf('doctrine.odm.mongodb.%s_metadata_cache', $documentManager['name'])),
            'setMetadataDriverImpl' => new Reference(sprintf('doctrine.odm.mongodb.%s_metadata_driver', $documentManager['name'])),
            'setProxyDir' => '%kernel.cache_dir%'.'/doctrine/odm/mongodb/Proxies',
            'setProxyNamespace' => '%doctrine.odm.mongodb.proxy_namespace%',
            'setAutoGenerateProxyClasses' => '%doctrine.odm.mongodb.auto_generate_proxy_classes%',
            'setHydratorDir' => '%kernel.cache_dir%'.'/doctrine/odm/mongodb/Hydrators',
            'setHydratorNamespace' => '%doctrine.odm.mongodb.hydrator_namespace%',
            'setAutoGenerateHydratorClasses' => '%doctrine.odm.mongodb.auto_generate_hydrator_classes%',
            'setDefaultDB' => $defaultDatabase,
            'setLoggerCallable' => array(new Reference('doctrine.odm.mongodb.logger'), 'logQuery'),
        );
        foreach ($methods as $method => $arg) {
            if ($odmConfigDef->hasMethodCall($method)) {
                $odmConfigDef->removeMethodCall($method);
            }
            $odmConfigDef->addMethodCall($method, array($arg));
        }

        // event manager
        $eventManagerName = isset($documentManager['event_manager']) ? $documentManager['event_manager'] : $documentManager['name'];
        $eventManagerId = sprintf('doctrine.odm.mongodb.%s_event_manager', $eventManagerName);
        if (!$container->hasDefinition($eventManagerId)) {
            $eventManagerDef = new Definition('%doctrine.odm.mongodb.event_manager_class%');
            $eventManagerDef->addTag('doctrine.odm.mongodb.event_manager');
            $eventManagerDef->setPublic(false);
            $container->setDefinition($eventManagerId, $eventManagerDef);
        }

        $odmDmArgs = array(
            new Reference(sprintf('doctrine.odm.mongodb.%s_connection', isset($documentManager['connection']) ? $documentManager['connection'] : $documentManager['name'])),
            isset($documentManager['database']) ? $documentManager['database'] : $defaultDatabase,
            new Reference(sprintf('doctrine.odm.mongodb.%s_configuration', $documentManager['name'])),
            new Reference($eventManagerId),
        );
        $odmDmDef = new Definition('%doctrine.odm.mongodb.document_manager_class%', $odmDmArgs);
        $odmDmDef->setFactoryClass('%doctrine.odm.mongodb.document_manager_class%');
        $odmDmDef->setFactoryMethod('create');
        $odmDmDef->addTag('doctrine.odm.mongodb.document_manager');
        $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_document_manager', $documentManager['name']), $odmDmDef);

        if ($documentManager['name'] == $defaultManagerName) {
            $container->setAlias(
                'doctrine.odm.mongodb.document_manager',
                new Alias(sprintf('doctrine.odm.mongodb.%s_document_manager', $documentManager['name']))
            );
            $container->setAlias(
                'doctrine.odm.mongodb.event_manager',
                new Alias(sprintf('doctrine.odm.mongodb.%s_event_manager', $documentManager['name']))
            );
        }
    }

    /**
     * Gets the configured document managers.
     *
     * @param array $options An array of configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function getDocumentManagers(array $options, ContainerBuilder $container)
    {
        $defaultDocumentManager = $options['default_document_manager'];

        $documentManagers = array();

        if (isset($options['document-managers'])) {
            $options['document_managers'] = $options['document-managers'];
        }

        if (isset($options['document_managers']) && $configDocumentManagers = $options['document_managers']) {

            if (isset($options['document_managers']['document-manager'])) {
                $options['document_managers']['document_manager'] = $options['document_managers']['document-manager'];
            }

            if (isset($options['document_managers']['document_manager']) && isset($options['document_managers']['document_manager'][0])) {
                // Multiple document managers
                $configDocumentManagers = $options['document_managers']['document_manager'];
            }
            foreach ($configDocumentManagers as $name => $documentManager) {
                $documentManagers[isset($documentManager['id']) ? $documentManager['id'] : $name] = $documentManager;
            }
        } else {
            $documentManagers = array($defaultDocumentManager => $options);
        }

        return $documentManagers;
    }

    /**
     * Loads the configured document manager metadata cache driver.
     *
     * @param string $defaultMetadataCacheDriver Driver name for the default metadata cache
     * @param array $config        A configured document manager array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManagerMetadataCacheDriver($defaultMetadataCacheDriver, array $documentManager, ContainerBuilder $container)
    {
        $dmMetadataCacheDriver = isset($documentManager['metadata-cache-driver']) ? $documentManager['metadata-cache-driver'] : (isset($documentManager['metadata_cache_driver']) ? $documentManager['metadata_cache_driver'] : $defaultMetadataCacheDriver);
        $type = is_array($dmMetadataCacheDriver) && isset($dmMetadataCacheDriver['type']) ? $dmMetadataCacheDriver['type'] : $dmMetadataCacheDriver;

        if ('memcache' === $type) {
            $memcacheClass = isset($dmMetadataCacheDriver['class']) ? $dmMetadataCacheDriver['class'] : sprintf('%%doctrine.odm.mongodb.cache.%s_class%%', $type);
            $cacheDef = new Definition($memcacheClass);
            $memcacheHost = isset($dmMetadataCacheDriver['host']) ? $dmMetadataCacheDriver['host'] : '%doctrine.odm.mongodb.cache.memcache_host%';
            $memcachePort = isset($dmMetadataCacheDriver['port']) ? $dmMetadataCacheDriver['port'] : '%doctrine.odm.mongodb.cache.memcache_port%';
            $memcacheInstanceClass = isset($dmMetadataCacheDriver['instance-class']) ? $dmMetadataCacheDriver['instance-class'] : (isset($dmMetadataCacheDriver['instance_class']) ? $dmMetadataCacheDriver['instance_class'] : '%doctrine.odm.mongodb.cache.memcache_instance_class%');
            $memcacheInstance = new Definition($memcacheInstanceClass);
            $memcacheInstance->addMethodCall('connect', array($memcacheHost, $memcachePort));
            $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_memcache_instance', $documentManager['name']), $memcacheInstance);
            $cacheDef->addMethodCall('setMemcache', array(new Reference(sprintf('doctrine.odm.mongodb.%s_memcache_instance', $documentManager['name']))));
        } else {
             $cacheDef = new Definition(sprintf('%%doctrine.odm.mongodb.cache.%s_class%%', $type));
        }
        $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_metadata_cache', $documentManager['name']), $cacheDef);
    }

    /**
     * Loads the configured connections.
     *
     * @param array $options An array of configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadConnections(array $options, ContainerBuilder $container)
    {
        $connections = $this->getConnections($options, $container);
        foreach ($connections as $name => $connection) {
            $odmConnArgs = array(
                isset($connection['server']) ? $connection['server'] : null,
                isset($connection['options']) ? $connection['options'] : array(),
                new Reference(sprintf('doctrine.odm.mongodb.%s_configuration', $name))
            );
            $odmConnDef = new Definition('%doctrine.odm.mongodb.connection_class%', $odmConnArgs);
            $container->setDefinition(sprintf('doctrine.odm.mongodb.%s_connection', $name), $odmConnDef);
        }
    }

    /**
     * Gets the configured connections.
     *
     * @param array $options An array of configuration options
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function getConnections(array $options, ContainerBuilder $container)
    {
        $defaultConnection = $options['default_connection'];

        $connections = array();
        if (isset($options['connections']) && $configConnections = $options['connections']) {
            if (isset($options['connections']['connection']) && isset($options['connections']['connection'][0])) {
                // Multiple connections
                $configConnections = $options['connections']['connection'];
            }
            foreach ($configConnections as $name => $connection) {
                $connections[isset($connection['id']) ? $connection['id'] : $name] = $connection;
            }
        } else {
            $connections = array($defaultConnection => $options);
        }
        return $connections;
    }

    /**
     * Loads an ODM document managers bundle mapping information.
     *
     * There are two distinct configuration possibilities for mapping information:
     *
     * 1. Specify a bundle and optionally details where the entity and mapping information reside.
     * 2. Specify an arbitrary mapping location.
     *
     * @example
     *
     *  doctrine.orm:
     *     mappings:
     *         MyBundle1: ~
     *         MyBundle2: yml
     *         MyBundle3: { type: annotation, dir: Documents/ }
     *         MyBundle4: { type: xml, dir: Resources/config/doctrine/mapping }
     *         MyBundle5:
     *             type: yml
     *             dir: [bundle-mappings1/, bundle-mappings2/]
     *             alias: BundleAlias
     *         arbitrary_key:
     *             type: xml
     *             dir: %kernel.dir%/../src/vendor/DoctrineExtensions/lib/DoctrineExtensions/Documents
     *             prefix: DoctrineExtensions\Documents\
     *             alias: DExt
     *
     * In the case of bundles everything is really optional (which leads to autodetection for this bundle) but
     * in the mappings key everything except alias is a required argument.
     *
     * @param array $documentManager A configured ODM entity manager.
     * @param Definition A Definition instance
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDocumentManagerBundlesMappingInformation(array $documentManager, Definition $odmConfigDef, ContainerBuilder $container)
    {
        // reset state of drivers and alias map. They are only used by this methods and children.
        $this->drivers = array();
        $this->aliasMap = array();

        $this->loadMappingInformation($documentManager, $container);
        $this->registerMappingDrivers($documentManager, $container);

        if ($odmConfigDef->hasMethodCall('setDocumentNamespaces')) {
            // TODO: Can we make a method out of it on Definition? replaceMethodArguments() or something.
            $calls = $odmConfigDef->getMethodCalls();
            foreach ($calls AS $call) {
                if ($call[0] == 'setDocumentNamespaces') {
                    $this->aliasMap = array_merge($call[1][0], $this->aliasMap);
                }
            }
            $method = $odmConfigDef->removeMethodCall('setDocumentNamespaces');
        }
        $odmConfigDef->addMethodCall('setDocumentNamespaces', array($this->aliasMap));
    }

    protected function loadConstraints($config, ContainerBuilder $container)
    {
        if ($container->hasParameter('validator.annotations.namespaces')) {
            $container->setParameter('validator.annotations.namespaces', array_merge(
                $container->getParameter('validator.annotations.namespaces'),
                array('mongodb' => 'Symfony\Bundle\DoctrineMongoDBBundle\Validator\Constraints\\')
            ));
        }
    }

    /**
     * @see AbstractDoctrineExtension
     */
    protected function getObjectManagerElementName($name)
    {
        return 'doctrine.odm.mongodb.' . $name;
    }

    /**
     * @see AbstractDoctrineExtension
     */
    protected function getMappingObjectDefaultName()
    {
        return 'Document';
    }

    /**
     * @see AbstractDoctrineExtension
     */
    protected function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/doctrine/metadata/mongodb';
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/doctrine/odm/mongodb';
    }

    /**
     * @return string
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     */
    public function getAlias()
    {
        return 'doctrine_mongo_db';
    }
}
