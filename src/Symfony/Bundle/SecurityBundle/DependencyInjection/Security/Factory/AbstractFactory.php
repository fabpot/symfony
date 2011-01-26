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

    protected function createAuthProvider($container, $id, $authProviderId, $userProviderId)
    {
        $authProvider = clone $container->getDefinition($authProviderId);

        $arguments = $authProvider->getArguments();
        $arguments[] = new Reference($userProviderId);
        $arguments[] = new Reference('security.account_checker');
        $arguments[] = $id;
        $authProvider->setArguments($arguments);

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

        if ($this->getRememberMeFromConfig($config)) {
            $listener->addTag('security.remember_me_aware', array('id' => $id, 'provider' => $userProviderId));
        }

        $listenerId.= '.'.$id;
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }

    protected function getRememberMeFromConfig($config)
    {
        if (array_key_exists('remember-me', $config) && false === $config['remember-me']) {
            return false;
        } else if (array_key_exists('remember_me', $config) && false === $config['remember_me']) {
            return false;
        }

        return true;
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
