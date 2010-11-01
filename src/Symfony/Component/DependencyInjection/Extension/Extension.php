<?php

namespace Symfony\Component\DependencyInjection\Extension;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Extension is a helper class that helps organize extensions better.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class Extension implements ExtensionInterface
{
    /**
     * Loads a specific configuration.
     *
     * @param string  $tag           The tag name
     * @param array   $config        An array of configuration values
     * @param ContainerBuilder $configuration A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load($tag, array $config, ContainerBuilder $configuration)
    {
        if (!method_exists($this, $method = $tag.'Load')) {
            throw new \InvalidArgumentException(sprintf('The tag "%s:%s" is not defined in the "%s" extension.', $this->getAlias(), $tag, $this->getAlias()));
        }

        $this->$method($config, $configuration);
    }

    /**
     * Remaps a set of config variables from the extension config to DIC parameters
     *
     * This method is essentially useful to remap a lot of parameters without writing
     * too much code, and it can be used in various ways:
     *
     *     $namespaces = array(
     *         // remap top-level config values to arbitrary names (no %s in the target string)
     *         '' => array(
     *             'session_create_success_route' => 'my_bundle.session_create.success_route',
     *             'template_renderer' => 'my_bundle.template.renderer',
     *             'template_theme' => 'my_bundle.template.theme',
     *         ),
     *         // remap sub-namespaces content into a specific parameter name (%s is replaced by the key of each config value in the sub-namespace)
     *         'auth' => 'my_bundle.auth.%s',
     *         'remember_me' => 'my_bundle.remember_me.%s',
     *         'form_name' => 'my_bundle.form.%s.name',
     *         'confirmation_email' => 'my_bundle.confirmation_email.%s',
     *     );
     *     $this->remapParametersByNamespace($config, $container, $namespaces);
     *
     *     // remap namespaces that are two-level deep by passing the first namespace ($config['class']) to the function
     *     $namespaces = array(
     *         'model' => 'my_bundle.model.%s.class',
     *         'form' => 'my_bundle.form.%s.class',
     *         'controller' => 'my_bundle.controller.%s.class'
     *     );
     *     $this->remapParametersByNamespace($config['class'], $container, $namespaces);
     *
     * @param array $config Configuration data
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param array $namespaces An array of 'namespace' => 'param.name.%s' mappings
     * @param bool $allowNullOverride whether 'null' is considered to be a value (true) or a placeholder (false)
     */
    protected function remapParametersByNamespace(array $config, ContainerBuilder $container, array $namespaces, $allowNullOverride = false)
    {
        foreach ($namespaces as $ns => $map) {
            if ($ns) {
                if (!isset($config[$ns])) {
                    continue;
                }
                $namespaceConfig = $config[$ns];
            } else {
                $namespaceConfig = $config;
            }
            if (is_array($map)) {
                $this->remapParameters($namespaceConfig, $container, $map, $allowNullOverride);
            } else {
                foreach ($namespaceConfig as $name => $value) {
                    if (null !== $value || $allowNullOverride) {
                        $container->setParameter(sprintf($map, $name), $value);
                    }
                }
            }
        }
    }

    /**
     * Remaps a set of config variables from the extension config to DIC parameters
     *
     * This method is used internally by remapParametersByNamespace, but you can also use it to remap
     * top-level variables instead of passing the an array with an empty key (@see remapParametersByNamespace):
     *
     *     $map = array(
     *         // remap top-level config values to arbitrary names (no %s in the target string)
     *         'session_create_success_route' => 'my_bundle.session_create.success_route',
     *         'template_renderer' => 'my_bundle.template.renderer',
     *         'template_theme' => 'my_bundle.template.theme',
     *     );
     *     $this->remapParameters($config, $container, $map);
     *
     * @param array $config Configuration data
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param array $namespaces An array of 'config.key' => 'parameter.name' mappings
     * @param bool $allowNullOverride whether 'null' is considered to be a value (true) or a placeholder (false)
     */
    protected function remapParameters(array $config, ContainerBuilder $container, array $map, $allowNullOverride = false)
    {
        foreach ($map as $name => $paramName) {
            if (isset($config[$name]) || ($allowNullOverride && array_key_exists($name, $config))) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }
}
