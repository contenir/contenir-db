<?php

namespace Contenir\Db\Model\Entity;

abstract class ResourceEntity extends AbstractEntity
{
    protected $routeId;

    /**
     * getRouteId
     *
     * @param  mixed $path
     *
     * @return String
     */
    public function getRouteId(string $path = ''): string
    {
        if ($this->routeId === null) {
            $routeId = sprintf(
                '%s-%s',
                $this->resource_type_id,
                $this->resource_id
            );

            $this->routeId = $routeId;
        }

        return sprintf('%s', join('/', array_filter([$this->routeId, $path])));
    }
}
