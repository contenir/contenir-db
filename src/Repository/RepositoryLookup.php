<?php

namespace Contenir\Db\Model\Repository;

use Contenir\Db\Model\Entity\AbstractEntity;

class RepositoryLookup
{
    /**
     * @var AbstractEntity
     */
    protected $entityPrototype = null;

    /**
     * @var AbstractEntity
     */
    protected $entityRelations = [];

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
