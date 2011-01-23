<?php

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RememberMeFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        if (!isset($config['key']) || empty($config['key'])) {
            throw new \RuntimeException('A "key" must be defined for each remember-me section.');
        }

        if (isset($config['provider'])) {
            throw new \RuntimeException('You must not set a user provider for remember-me.');
        }

        // authentication provider
        $authenticationProviderId = 'security.authentication.provider.rememberme.'.$id;
        $container
            ->register($authenticationProviderId, '%security.authentication.provider.rememberme.class%')
            ->setArguments(array(new Reference('security.account_checker'), $config['key']))
            ->setPublic(false)
        ;

        // remember me services
        if (isset($config['services'])) {
            $config['service'] = $config['services'];
        }
        if (!isset($config['service'])) {
            if (isset($config['token_provider'])) {
                $config['token-provider'] = $config['token_provider'];
            }
            if (isset($config['token-provider'])) {
                $rememberMeServicesId = $this->getRememberMeServicesId('persistent');
            } else {
                $rememberMeServicesId = $this->getRememberMeServicesId('simplehash');
            }

            if ($container->hasDefinition('security.logout_listener.'.$id)) {
                $container
                    ->getDefinition('security.logout_listener.'.$id)
                    ->addMethodCall('addHandler', array(new Reference($rememberMeServicesId.$id)))
                ;
            }
        } else {
            $rememberMeServicesId = $this->getRememberMeServicesId($config['service']);
        }

        $rememberMeServices = $container->setDefinition($rememberMeServicesId.$id, clone $container->getDefinition($rememberMeServicesId));
        $rememberMeServicesId .= '.'.$id;
        $rememberMeServices->setArgument(1, $config['key']);

        if (!isset($config['service']) && isset($config['token-provider'])) {
            $rememberMeServices->addMethodCall('setTokenProvider', array(
                new Reference('security.rememberme.token.provider.'.$config['token-provider'])
            ));
        }

        // attach to remember-me aware listeners
        $userProviders = array();
        foreach ($container->findTaggedServiceIds('security.listener.rememberme_aware') as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['id']) || $attribute['id'] !== $id) {
                    continue;
                }

                if (!isset($attribute['provider'])) {
                    throw new \RuntimeException('Each "security.listener.rememberme_aware" tag must have a provider attribute.');
                }

                $userProviders[] = new Reference($attribute['provider']);
                $container
                    ->getDefinition($serviceId)
                    ->addMethodCall('setRememberMeServices', array(new Reference($rememberMeServicesId)))
                ;
            }
        }
        $rememberMeServices->setArgument(0, $userProviders);

        // remember-me listener
        $listenerId = 'security.authentication.listener.rememberme.'.$id;
        $listener = $container->setDefinition($listenerId, clone $container->getDefinition('security.authentication.listener.rememberme'));
        $listener->setArgument(1, new Reference($rememberMeServicesId));
        $listener->setArgument(2, new Reference($authenticationProviderId));

        return array($authenticationProviderId, $listenerId, $defaultEntryPoint);
    }

    protected function getRememberMeServicesId($name)
    {
        return 'security.authentication.rememberme.services.'.$name;
    }

    public function getPosition()
    {
        return 'remember_me';
    }

    public function getKey()
    {
        return 'remember-me';
    }
}
