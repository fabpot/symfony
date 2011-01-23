<?php

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RememberMeFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        // shared key between authentication provider, and token
        $sharedKey = isset($config['key']) ? $config['key'] : new Parameter('security.rememberme.key');

        // authentication provider
        $authenticationProvider = 'security.authentication.provider.rememberme.'.$id;
        $container
            ->register($authenticationProvider, '%security.authentication.provider.rememberme.class%')
            ->setArguments(array(new Reference('security.account_checker'), $sharedKey))
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
        $arguments = $rememberMeServices->getArguments();
        $arguments[0] = new Reference($userProvider);

        $rememberMeServices->setArguments($arguments);

        if (!isset($config['service'])) {
            $methodCalls = array();
            foreach ($rememberMeServices->getMethodCalls() as $call) {
                list($method, $arguments) = $call;

                if ('setTokenProvider' === $method) {
                    $methodCalls[] = array($method, array(new Reference('security.rememberme.token.provider.'.$config['token-provider'])));
                }
                if ('setKey' === $method) {
                    $methodCalls[] = array($method, array($sharedKey));
                }
            }

            $rememberMeServices->setMethodCalls($methodCalls);
        }

        // attach to rememberme aware listeners
        $tags = $container->findTaggedServiceIds('security.listener.rememberme_aware_'.$id);
        foreach (array_keys($tags) as $service) {
            $container
                ->getDefinition($service)
                ->addMethodCall('setRememberMeServices', array(new Reference($rememberMeServicesId.$id)))
            ;
        }

        // remember-me listener
        $listenerId = 'security.authentication.listener.rememberme.'.$id;
        $listener = $container->setDefinition($listenerId, clone $container->getDefinition('security.authentication.listener.rememberme'));
        $arguments = $listener->getArguments();
        $arguments[1] = new Reference($rememberMeServicesId.$id);
        $arguments[2] = new Reference($authenticationProvider);
        $listener->setArguments($arguments);

        return array($authenticationProvider, $listenerId, $defaultEntryPoint);
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
