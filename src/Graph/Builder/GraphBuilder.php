<?php

namespace PhpDevCommunity\Sql\Graph\Builder;


use LogicException;

final class GraphBuilder
{
    /**
     * @var array
     */
    private array $dataFlattened;

    /**
     * @var array<Table>
     */
    private array $tables;
    private string $primaryKey;
    private ?Table $primaryTable = null;

    public function __construct(
        array  &$dataFlattened,
        array  $tables,
        string $primaryKey = 'id'
    )
    {

        $this->dataFlattened = &$dataFlattened;
        foreach ($tables as $table) {
            if (!$table instanceof Table) {
                throw new LogicException('Table must be an instance of Table');
            }
            $this->tables[$table->getAlias()] = $table;
            if ($table->getParent() === null) {
                $this->primaryTable = $table;
            }
        }
        $this->primaryKey = $primaryKey;
        if ($this->primaryTable === null) {
            throw new LogicException('Primary table not found, need at least one table with no parent');
        }
    }

    public function buildGraph(): array
    {
        $graph = [];

        if (count($this->dataFlattened) === 0) {
            throw new LogicException('No data to build graph from, dataFlattened is empty');
        }

        foreach ($this->dataFlattened as $row) {
            $this->insertRowIntoGraph($graph, $row);
        }
        $this->dataFlattened = [];

        $this->cleanGraph($graph);
        return reset($graph);
    }

    private function insertRowIntoGraph(array &$graph, array $row): void
    {
        $references = [];

        foreach ($row as $key => $value) {
            [$entity, $attribute] = explode('__', $key);
            $parent = $this->tables[$entity]->getParent();
            $table = $this->tables[$entity];
            foreach ($table->getChildren() as $child) {
                if ($child->getSourceForeignKey() === $attribute) {
                    continue 2;
                }
            }

            $foreignKey = $table->getSourceForeignKey();
            if ($attribute === $foreignKey) {
                continue;
            }
            if (!isset($references[$entity])) {
                $parent = $parent !== null ? $parent->getAlias() : null;

                if ($parent === null) {
                    if (!isset($graph[$entity])) {
                        $graph[$entity] = [];
                    }
                    $references[$entity] = &$graph[$entity];
                } else {
                    if (!isset($references[$parent])) {
                        throw new LogicException("Parent is not defined: $parent");
                    }

                    $parentNode = &$references[$parent];
                    if (!isset($parentNode[$entity])) {
                        $parentNode[$entity] = [];
                    }
                    $references[$entity] = &$parentNode[$entity];
                }
            }

            if ($attribute === $this->primaryKey) {
                if (!isset($references[$entity][$value])) {
                    $references[$entity][$value] = [$attribute => $value];
                }
                $references[$entity] = &$references[$entity][$value];
            } else {
                $references[$entity][$attribute] = $value;
            }
        }
    }

    private function cleanGraph(array &$graph): void
    {
        $updatedGraph = [];
        $primaryTableAlias = $this->primaryTable->getAlias();
        foreach ($graph as $key => &$value) {
            if (!is_array($value)) {
                $updatedGraph[$key] = $value;
                continue;
            }

            if (array_key_exists($key, $this->tables)) {
                $value = array_values($value);
            }

            $this->cleanGraph($value);
            if ($this->allValuesAreNull($value)) {
                $value = null;
            }

            if (!array_key_exists($key, $this->tables)) {
                $updatedGraph[$key] = $value;
                continue;
            }
            if ($primaryTableAlias === $key) {
                $updatedGraph[$key] = $value;
                continue;
            }

            $oneToMany = $this->tables[$key]->isOneToMany();
            $keyRelation = $this->tables[$key]->getRelationKey();
            $updatedGraph[$keyRelation] = $oneToMany === false ? ($value[0] ?? null) : $value ?? [];
        }
        $graph = $updatedGraph;
        unset($updatedGraph);
    }

    private function allValuesAreNull(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_array($value) && $value !== null) {
                return false;
            }

            if (is_array($value) && !empty($value)) {
                return false;
            }
        }
        return true;
    }

}