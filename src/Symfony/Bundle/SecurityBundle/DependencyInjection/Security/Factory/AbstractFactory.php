<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * FormLoginFactory creates services for form login authentication.
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
abstract class AbstractFactory
{
    protected $options = array(
        'check_path'                     => '/login_check',
        'login_path'                     => '/login',
        'use_forward'                    => false,
        'always_use_default_target_path' => false,
        'default_target_path'            => '/',
        'target_path_parameter'          => '_target_path',
        'use_referer'                    => false,
        'failure_path'                   => null,
        'failure_forward'                => false,
    );

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        $authProviderId = $this->createAuthProvider($container, $id, 'security.authentication.provider.dao', $userProviderId);

        $listenerId = $this->createListener($container, $id, 'security.authentication.listener.form', $userProviderId, $config);

        $entryPointId = $this->createEntryPoint($container, $id, $config, 'security.authentication.form_entry_point');

        return array($authProviderId, $listenerId, $entryPointId);
    }

    protected function createAuthProvider($container, $id, $authProviderId, $userProviderId)
    {
        $authProvider = clone $container->getDefinition($authProviderId);

        $authProvider->addArgument(new Reference($userProviderId));
        $authProvider->addArgument(new Reference('security.account_checker'));
        $authProvider->addArgument($id);

        $authProvider->setSynthetic(false);
        $authProvider->setPublic(false);
        $authProvider->addTag('security.authentication_provider');

        $authProviderId.= '.'.$id;
        $container->setDefinition($authProviderId, $authProvider);

        return $authProviderId;
    }

    protected function createListener($container, $id, $listenerId, $userProviderId, $config)
    {
        $listener = clone $container->getDefinition($listenerId);

        $listener->setArgument(3, $id);

        $listener->setArgument(4, $this->getOptionsFromConfig($config));

        // success handler
        if (isset($config['success_handler'])) {
            $config['success-handler'] = $config['success_handler'];
        }
        if (isset($config['success-handler'])) {
            $listener->setArgument(5, new Reference($config['success-handler']));
        }

        // failure handler
        if (isset($config['failure_handler'])) {
            $config['failure-handler'] = $config['failure_handler'];
        }
        if (isset($config['failure-handler'])) {
            $listener->setArgument(6, new Reference($config['failure-handler']));
        }

        if ($this->isRememberMeAware($config)) {
            $listener->addTag('security.remember_me_aware', array('id' => $id, 'provider' => $userProviderId));
        }

        $listenerId.= '.'.$id;
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }

    public function createEntryPoint($container, $id, $config, $entryPointId)
    {
        $entryPoint = clone $container->getDefinition($entryPointId);

        $entryPointId.= '.'.$id;
        $container->setDefinition($entryPointId, $entryPoint);

        return $entryPointId;
    }

    protected function isRememberMeAware($config)
    {
        if (array_key_exists('remember-me', $config) && false === $config['remember-me']) {
            return false;
        } else if (array_key_exists('remember_me', $config) && false === $config['remember_me']) {
            return false;
        }

        return true;
    }

    protected function addOption($name, $default = null)
    {
        $this->options[$name] = $default;
    }

    protected function getOptionsFromConfig($config)
    {
        $options = $this->options;

        foreach (array_keys($options) as $key) {
            if (isset($config[$key])) {
                $options[$key] = $config[$key];
            }
        }

        return $options;
    }
}
