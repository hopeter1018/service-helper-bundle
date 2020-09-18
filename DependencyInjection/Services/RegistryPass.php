<?php

declare(strict_types=1);

namespace HoPeter1018\ServiceHelperBundle\DependencyInjection\Services;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * RegistryPass
 * Generated: 2020-09-18T02:33:59+00:00.
 */
class RegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('ho_peter1018.service_helper.services.registry_registry')) {
            return;
        }
        $definition = $container->findDefinition('ho_peter1018.service_helper.services.registry_registry');

        // find all service IDs with the ho_peter1018.service_helper.services.registry_pool tag
        $taggedServices = $container->findTaggedServiceIds('ho_peter1018.service_helper.services.registry_pool');

        foreach ($taggedServices as $id => $tags) {
            // add the service to the registry service
            $definition->addMethodCall('add', [$id, new Reference($id), $tags]);
        }
    }
}
