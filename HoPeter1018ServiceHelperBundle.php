<?php

namespace HoPeter1018\ServiceHelperBundle;

use HoPeter1018\ServiceHelperBundle\DependencyInjection\Services\RegistryPass;
use HoPeter1018\ServiceHelperBundle\Services\Registry\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HoPeter1018ServiceHelperBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegistryPass());
        $container->registerForAutoconfiguration(RegistryInterface::class)->addTag('ho_peter1018.service_helper.services.registry_pool');
    }
}
