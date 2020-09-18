<?php

declare(strict_types=1);

namespace HoPeter1018\ServiceHelperBundle\Service\Registry;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class BaseRegistry implements ContainerAwareInterface, RegistryInterface
{
    use ContainerAwareTrait;

    /**
     * @var array|mixed[]|RegistryInterface[]
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

    public function getAll()
    {
        return $this->providers;
    }

    public function getChoices()
    {
        $choices = [];
        foreach ($this->providers as $id => $provider) {
            $choices[$provider->getChoiceName()] = $id;
        }

        return $choices;
    }

    public function getIds(): array
    {
        $choices = [];
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
