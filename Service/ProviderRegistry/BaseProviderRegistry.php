<?php

declare(strict_types=1);

namespace EasternColor\CoreBundle\Services\ProviderRegistry;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class BaseProviderRegistry implements ContainerAwareInterface, ProviderRegistryInterface
{
    use ContainerAwareTrait;

    /**
     * [$providers description].
     *
     * @var array|mixed[]
     */
    private $providers;

    public function __construct()
    {
        $this->providers = [];
    }

    public function add($id, $provider, $tags)
    {
        $this->providers[$id] = $provider;
    }

    public function gets()
    {
        return $this->providers;
    }

    public function getChoices()
    {
        $choices = [];
        /* @var $provider DeliveryModuleProviderInerface */
        foreach ($this->providers as $id => $provider) {
            $choices[$provider->getChoiceName()] = $id;
        }

        return $choices;
    }

    public function getIds(): array
    {
        $choices = [];
        /* @var $provider DeliveryModuleProviderInerface */
        foreach ($this->providers as $id => $provider) {
            $choices[] = $id;
        }

        return $choices;
    }

    public function getMaps($prefix)
    {
        $result = [];
        foreach ($this->providers as $serviceId => $provider) {
            $result[$serviceId] = [$prefix.md5($serviceId)];
        }

        return $result;
    }

    public function getByServiceId($id)
    {
        return (isset($this->providers[$id])) ? $this->providers[$id] : null;
    }
}
