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
use Symfony\Component\Yaml\Yaml;

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
        $options = $this->mergeConfigs($configs);

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
     * @param array $configs An array of configuration arrays to merge
     * @return array The merged configuration array
     */
    public function mergeConfigs(array $configs)
    {
        $defaultConfig = Yaml::load( __DIR__.'/../Resources/config/mongodb_defaults.yml');
        $defaultOptions = $defaultConfig['doctrine_odm.mongodb'];

        $mergedConfig = $defaultOptions;

        foreach ($configs as $config) {
            $config = self::normalizeKeys($config);

            // normalize connection versus connections
            if (isset($config['connection']) && is_array($config['connection'])) {
                $config['connections'] = self::normalizeConfig($config, 'connection');
                unset($config['connection']);

                $config['connections'] = self::remapConfigArray($config['connections'], 'id');
            }

            // normalize document_manager versus document_managers
            if (isset($config['document_manager']) && is_array($config['document_manager'])) {
                $config['document_managers'] = self::normalizeConfig($config, 'document_manager');
                unset($config['document_manager']);

                $config['document_managers'] = self::remapConfigArray($config['document_managers'], 'id');
            }

            // normalize document_manager versus document_managers
            if (isset($config['mapping']) && is_array($config['mapping'])) {
                $config['mappings'] = self::normalizeConfig($config, 'mapping');
                unset($config['mapping']);

                $config['mappings'] = self::remapConfigArray($config['mappings'], 'name');
            }

            // normalize the mapping versus mappings that can be beneath a document_manager
            if (isset($config['document_managers']) && is_array($config['document_managers'])) {
                foreach ($config['document_managers'] as $key => $dmConfig) {
                    if (isset($dmConfig['mapping']) && is_array($dmConfig['mapping'])) {
                        $config['document_managers'][$key]['mappings'] = self::normalizeConfig($config['document_managers'][$key], 'mapping');
                        unset($config['document_managers'][$key]['mapping']);

                        $config['document_managers'][$key]['mappings'] = self::remapConfigArray($config['document_managers'][$key]['mappings'], 'name');
                    }
                }
            }

            $mergedConfig = $this->mergeOptions($mergedConfig, $config, $defaultOptions);
        }

        return $mergedConfig;
    }

    /**
     * Merges a single level of configuration options.
     *
     * This method is taken verbatim from FrameworkExtension.
     *
     * @return array The merged options
     */
    protected function mergeOptions(array $current, array $new, array $default, $basePath = null)
    {
        if ($unsupportedOptions = array_diff_key($new, $default)) {
            throw new \InvalidArgumentException('The following options are not supported: '.implode(', ', array_keys($unsupportedOptions)));
        }

        foreach ($default as $key => $defaultValue) {
            if (array_key_exists($key, $new)) {
                $optionPath = $basePath ? $basePath.'.'.$key : $key;
                $current[$key] = $this->mergeOptionValue($current[$key], $new[$key], $defaultValue, $optionPath);
            }
        }

        return $current;
    }

    /**
     * Merges an option value.
     *
     * @param mixed  $current    The value of the option before merging
     * @param mixed  $new        The new value to be merged
     * @param mixed  $default    The corresponding default value for the option
     * @param string $optionPath Property path for the option
     * @return mixed The merged value
     * @throws InvalidArgumentException When an invalid option is found
     */
    protected function mergeOptionValue($current, $new, $defaultValue, $optionPath)
    {
        // Ensure that the new value's type is an array if expected
        if (is_array($defaultValue) && !is_array($new)) {
            throw new \InvalidArgumentException(sprintf('Expected array type for option "%s", %s given', $optionPath, gettype($new)));
        }

        switch ($optionPath) {
            // the single-connection options array always just overwrite
            case 'options':
                return $new;

            // simple array_merge with no internal validation
            case 'mappings':
            case 'connections':
            case 'document_managers':
                return array_merge($current, $new);

            // default logic here
        }

        return is_array($defaultValue) ? $this->mergeOptions($current, $new, $defaultValue, $optionPath) : $new;
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

        if (isset($options['document_managers']) && count($options['document_managers'])) {
            return $options['document_managers'];
        } else {
            return array($defaultDocumentManager => $options);
        }
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

        if (isset($options['connections']) && $configConnections = $options['connections']) {
            // multiple connections
            return $options['connections'];
        } else {
            // single connection - use the default connection name
            return array($defaultConnection => $options);
        }
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
     * Remaps an indexed array to a hashed array, using the value of the
     * given field as the new array key.
     *
     *     $config = array(array('id' => 'foo', 'var' => 'val'));
     *     self::remapConfigArray($config, 'id');
     *
     *     // returns array('id' => array('var' => 'val'));
     *
     * @param array $config The source array
     * @param  string $field The field name to use as the key
     * @return array
     */
    protected static function remapConfigArray(array $config, $field)
    {
        // map any id keys to be the name of the connection
        foreach ($config as $name => $val) {
            if (isset($val[$field])) {
                $newKey = $val[$field];
                unset($val[$field]);

                $config[$newKey] = $val;
                unset($config[$name]);
            }
        }

        return $config;
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
