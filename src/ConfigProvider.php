<?php

namespace Contenir\Db\Model;

use Laminas\Db\Adapter\Adapter;

class ConfigProvider
{
    /**
     * Retrieve default laminas-paginator configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'model'        => $this->getDbModelConfig(),
        ];
    }

    /**
     * Retrieve dependency configuration for laminas-paginator.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases'   => [],
            'factories' => [
                Repository\RepositoryLookup::class => Repository\Factory\RepositoryLookupFactory::class,
            ]
        ];
    }

    /**
     * Provide default route plugin manager configuration.
     *
     * @return array
     */
    public function getDbModelConfig()
    {
        return [
        	'adapter' => Adapter::class
        ];
    }
}
