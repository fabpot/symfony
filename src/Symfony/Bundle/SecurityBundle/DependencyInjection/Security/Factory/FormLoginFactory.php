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
        $this->options['username_parameter'] = '_username';
        $this->options['password_parameter'] = '_password';
        $this->options['post_only'] = true;
    }

    protected function createAuthProvider($container, $id, $authProviderId, $userProviderId)
    {
        $authProviderId = parent::createAuthProvider($container, $id, $authProviderId, $userProviderId);

        $authProvider = $container->getDefinition($authProviderId);
        $arguments = $authProvider->getArguments();
        $arguments[] = new Reference('security.encoder_factory');
        $authProvider->setArguments($arguments);

        return $authProviderId;
    }

    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPoint)
    {
        $authProviderId = $this->createAuthProvider($container, $id, 'security.authentication.provider.dao', $userProviderId);

        $authProvider = $container->getDefinition($authProviderId);
        $arguments = $authProvider->getArguments();
        $arguments[] = new Reference('security.encoder_factory');
        $authProvider->setArguments($arguments);

        $listenerId = $this->createListener($container, $id, 'security.authentication.listener.form', $userProviderId, $config);

        $entryPointId = 'security.authentication.form_entry_point';
        $entryPoint = clone $container->getDefinition($entryPointId);

        $arguments = $container->getDefinition($listenerId)->getArguments();
        $entryPoint->setArguments(array($arguments[4]['login_path'], $arguments[4]['use_forward']));

        $entryPointId.= '.'.$id;
        $container->setDefinition($entryPointId, $entryPoint);

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
