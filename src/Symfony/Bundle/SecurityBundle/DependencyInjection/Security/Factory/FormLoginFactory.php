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
class FormLoginFactory extends AbstractFactory implements SecurityFactoryInterface
{
    public function __construct()
    {
        $this->addOption('username_parameter', '_username');
        $this->addOption('password_parameter', '_password');
        $this->addOption('post_only', true);
    }

    protected function createAuthProvider($container, $id, $authProviderId, $userProviderId)
    {
        $authProviderId = parent::createAuthProvider($container, $id, $authProviderId, $userProviderId);

        $authProvider = $container->getDefinition($authProviderId);
        $authProvider->addArgument(new Reference('security.encoder_factory'));

        return $authProviderId;
    }

    public function createEntryPoint($container, $id, $config, $entryPointId)
    {
        $entryPointId = parent::createEntryPoint($container, $id, $config, $entryPointId);

        $entryPoint = clone $container->getDefinition($entryPointId);

        $entryPoint->setArguments(array($config['login_path'], $config['use_forward']));

        $entryPointId.= '.'.$id;
        $container->setDefinition($entryPointId, $entryPoint);
    }

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPoint)
    {
        $authProviderId = $this->createAuthProvider($container, $id, 'security.authentication.provider.dao', $userProviderId);

        $listenerId = $this->createListener($container, $id, 'security.authentication.listener.form', $userProviderId, $config);

        $arguments = $container->getDefinition($listenerId)->getArguments();
        $entryPointId = $this->createEntryPoint($container, $id, $arguments[4], 'security.authentication.form_entry_point');

        return array($authProviderId, $listenerId, $entryPointId);
    }

    public function getPosition()
    {
        return 'form';
    }

    public function getKey()
    {
        return 'form-login';
    }
}
