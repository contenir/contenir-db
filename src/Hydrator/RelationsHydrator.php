<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Hydrator;

use Contenir\Db\Model\Entity\AbstractEntity;
use Contenir\Db\Model\Exception\InvalidArgumentException;
use Contenir\Db\Model\Exception\RuntimeException;
use Laminas\Hydrator\ObjectPropertyHydrator;

class RelationsHydrator extends ObjectPropertyHydrator
{
    protected $container;
    protected $relations;

    public function __construct($container, array $relations)
    {
        $this->container = $container;
        $this->relations = $relations;
    }

    public function hydrate($data, $object)
    {
        $object->getEventManager()->attach('loadRelation', function ($e) {
            $target = $e->getTarget();
            $params = $e->getParams();

            $relationName = $params['relation'];
            if (array_key_exists($relationName, $this->relations)) {
                $target->{$relationName} = $this->fetchRelation($this->relations[$relationName], $target->getArrayCopy());
            }
        });

        return $object;
    }

    protected function fetchRelation(
        $relationConfig,
        $data = []
    ) {
        $relationDefinition  = $this->getRelationDefinition($relationConfig);
        $relationType        = $relationDefinition['relationType'];
        $relationColumn      = $relationDefinition['relationColumn'];
        $relationTableClass  = $relationDefinition['relationTableClass'];
        $relationTableColumn = $relationDefinition['relationTableColumn'];
        $relationCondition   = $relationDefinition['relationCondition'];
        $relationVia         = $relationDefinition['relationVia'];
        $relationOrder       = $relationDefinition['relationOrder'];

        $lookup       = array_combine($relationTableColumn, $relationColumn);
        $tableGateway = $this->container->getContainer()->get($relationTableClass);

        $rows     = [];
        $where    = [];
        $joinFrom = null;
        $joinTo   = null;

        if (! empty($relationVia)) {
            $joinFrom = $tableGateway->getTable();
            $joinTo   = $relationVia['table'];
            if (empty($relationVia['joinCondition'])) {
                $relationVia['joinCondition'] = [];
                foreach (array_keys($lookup) as $joinField) {
                    $joinFromField                  = $joinField;
                    $joinToField                    = $relationVia['join'] ?? $joinField;
                    $relationVia['joinCondition'][] = sprintf(
                        '%s.%s = %s.%s',
                        $joinFrom,
                        $joinFromField,
                        $joinTo,
                        $joinToField
                    );
                }
                if (is_array($relationVia['joinCondition'])) {
                    $relationVia['joinCondition'] = join(' ', $relationVia['joinCondition']);
                }
            }
        }

        foreach ($lookup as $column => $value) {
            if (isset($data[$value])) {
                if (! empty($relationVia)) {
                    $matchViaColumn = $relationVia['column'] ?? $value;
                    $matchColumn    = sprintf('%s.%s', $joinTo, $matchViaColumn);
                } else {
                    $matchColumn = $column;
                }
                $where[$matchColumn] = $data[$value];
            }
        }

        foreach ($relationCondition as $key => $condition) {
            $where[$key] = $condition;
        }

        if (count($where)) {
            $select = $tableGateway->select();
            $select->where($where);

            if (count($relationVia)) {
                $select->join($relationVia['table'], $relationVia['joinCondition'], []);
            }

            $tableGateway->prepareSelect($select, null, $relationOrder);

            switch ($relationType) {
                case AbstractEntity::RELATION_SINGLE:
                    $results = $tableGateway->selectWith($select);
                    $rows    = $results->current();
                    break;

                default:
                    $results = $tableGateway->selectWith($select);
                    $rows    = [];

                    foreach ($results as $row) {
                        $rows[] = $row;
                    }
                    break;
            }
        }

        return $rows;
    }

    protected function getRelationDefinition(array $relationDefinition): array
    {
        if (! isset($relationDefinition['column'])) {
            throw new InvalidArgumentException('Relation column is not set');
        }
        $relationColumn = (array) $relationDefinition['column'];

        if (! isset($relationDefinition['table'])) {
            throw new RuntimeException('Relation table data is not set');
        }
        $relationTable = $relationDefinition['table'];

        if (! is_string($relationTable['class'])) {
            throw new InvalidArgumentException('Relation table class is not set');
        }

        $relationType        = $relationDefinition['type'] ?? AbstractEntity::RELATION_MANY;
        $relationTableClass  = $relationTable['class'];
        $relationTableColumn = $relationTable['column'] ?? $relationColumn;
        $relationTableColumn = (array) $relationTableColumn;

        $where = $relationDefinition['where'] ?? [];
        $order = $relationDefinition['order'] ?? [];
        $via   = $relationDefinition['via']   ?? [];
        if (! empty($via)) {
            if (! is_string($via['table'])) {
                throw new InvalidArgumentException('Via table is not set');
            }
        }

        if (count($relationColumn) !== count($relationTableColumn)) {
            throw new InvalidArgumentException('Column counts of relations do not match');
        }

        return [
            'relationType'        => $relationType,
            'relationColumn'      => $relationColumn,
            'relationTableClass'  => $relationTableClass,
            'relationTableColumn' => (array) $relationTableColumn,
            'relationVia'         => (array) $via,
            'relationCondition'   => (array) $where,
            'relationOrder'       => (array) $order
        ];
    }
}
