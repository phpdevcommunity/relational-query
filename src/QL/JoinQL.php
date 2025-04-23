<?php

namespace PhpDevCommunity\Sql\QL;

use LogicException;
use PDO;
use PDOStatement;
use PhpDevCommunity\Sql\Graph\Builder\GraphBuilder;
use PhpDevCommunity\Sql\Graph\Builder\Table;
use PhpDevCommunity\Sql\Select;

final class JoinQL
{
    private PDO $pdo;
    private ?Select $selectQuery = null;
    private ?string $firstTable = null;
    private ?string $firstAlias = null;
    private array $joins = [];
    private string $primaryKey;
    private ?array $lastRow = null;
    private ?int $limit = null;

    public function __construct(PDO $pdo, string $primaryKey = 'id')
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->primaryKey = $primaryKey;
    }

    public function select(string $table, string $aliasTable, array $columns): self
    {
        self::resolveColumns($aliasTable, $columns);

        $this->selectQuery = (new Select($columns))->from($table, $aliasTable);
        $this->firstTable = $table;
        $this->firstAlias = $aliasTable;
        return $this;
    }

    public function addSelect(string $aliasTable, array $columns): self
    {
        self::resolveColumns($aliasTable, $columns);

        if ($this->selectQuery === null) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query ' .
                'before adding a SELECT clause.'
            );
        }
        $this->selectQuery->select(...$columns);
        return $this;

    }

    public function where(string ...$where): self
    {
        if ($this->selectQuery === null) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query ' .
                'before adding a WHERE clause.'
            );
        }
        $this->selectQuery->where(...$where);
        return $this;
    }

    public function orderBy($sort, $order = 'ASC'): self
    {
        if ($this->selectQuery === null) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query ' .
                'before adding an ORDER BY clause.'
            );
        }
        $this->selectQuery->orderBy($sort, $order);
        return $this;
    }

    public function setMaxResults(?int $maxResults): self
    {
        if ($this->selectQuery === null) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query ' .
                'before adding a LIMIT clause.'
            );
        }
        $this->limit = $maxResults;
        return $this;
    }

    public function leftJoin(
        string $fromTable,            // The source table (the table to join from or alias)
        string $toTable,              // The target table (the table to join to)
        string $toTableAlias,         // The alias for the joined table (used in the query)
        array  $joinConditions,       // The conditions for the JOIN clause (e.g., ['posts.user_id', '=', 'users.id'])
        bool   $isOneToMany = false,  // Whether the relationship is OneToMany (default is false for OneToOne)
        string $relationKey = null,   // The key to use for the relationship in the result set (e.g., 'user' to replace 'user_id')
        string $sourceForeignKey = null // The foreign key in the source table (e.g., 'user_id' in 'posts')
    ): self
    {
        if ($this->selectQuery === null) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query ' .
                'before adding a LEFT JOIN clause.'
            );
        }
        $this->joins[$toTableAlias] = [
            'type' => 'left',
            'from' => $fromTable,
            'to' => $toTable,
            'alias' => $toTableAlias,
            'key_relation' => $relationKey ?? $toTableAlias,
            'condition' => $joinConditions,
            'one_to_many' => $isOneToMany,
            'foreign_key' => $sourceForeignKey
        ];
        $this->selectQuery->leftJoin(sprintf('%s %s %s', $toTable, $toTableAlias, sprintf('ON %s', implode(' AND ', $joinConditions))));

        return $this;
    }

    public function innerJoin(
        string $fromTable,            // The source table (the table to join from or alias)
        string $toTable,              // The target table (the table to join to)
        string $toTableAlias,         // The alias for the joined table (used in the query)
        array  $joinConditions,       // The conditions for the JOIN clause (e.g., ['posts.user_id', '=', 'users.id'])
        bool   $isOneToMany = false,  // Whether the relationship is OneToMany (default is false for OneToOne)
        string $relationKey = null,   // The key to use for the relationship in the result set (e.g., 'user' to replace 'user_id')
        string $sourceForeignKey = null // The foreign key in the source table (e.g., 'user_id' in 'posts')
    ): self
    {
        if ($this->selectQuery === null) {
            throw new LogicException(
                'You must call the select() method first to define the main table for the query ' .
                'before adding a INNER JOIN clause.'
            );
        }
        $this->joins[$toTableAlias] = [
            'type' => 'inner',
            'from' => $fromTable,
            'to' => $toTable,
            'alias' => $toTableAlias,
            'key_relation' => $relationKey ?? $toTableAlias,
            'condition' => $joinConditions,
            'one_to_many' => $isOneToMany,
            'foreign_key' => $sourceForeignKey
        ];
        $this->selectQuery->innerJoin(sprintf('%s %s %s', $toTable, $toTableAlias, sprintf('ON %s', implode(' AND ', $joinConditions))));
        return $this;
    }

    public function  getResultIterator(array $params = []): iterable
    {
        $db = $this->executeQuery($this->selectQuery, $params);
        $this->lastRow = null;
        $count = 0;
        while (($rows = $this->fetchIterator($db)) !== null) {
            if ($this->limit !== null && $count >= $this->limit) {
                break;
            }
            $count++;
            if ($rows instanceof \Traversable) {
                $rows = iterator_to_array($rows);
            }

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                yield $row;
            }
        }

        $db->closeCursor();
    }

    public function getOneOrNullResult(array $params = []): ?array
    {
        foreach ($this->getResultIterator($params) as $row) {
            if ($row instanceof \Traversable) {
                $row = iterator_to_array($row)[0] ?? null;
            }
            return $row;
        }

        return null;
    }

    public function getResult(array $params = []): array
    {
        if ($this->limit !== null) {
            $data = [];
            foreach ($this->getResultIterator($params) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        $db = $this->executeQuery($this->selectQuery, $params);
        return $this->buildGraph($db->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getQuery(): string
    {
        return $this->selectQuery->__toString();
    }

    private function fetchIterator(PDOStatement $db): iterable
    {
        $data = null;
        if ($this->lastRow !== null) {
            $data[] = $this->lastRow;
            $this->lastRow = null;
        }

        $previous = null;
        $primaryKey = sprintf('%s__%s', $this->firstAlias, $this->primaryKey);
        while ($row = $db->fetch(PDO::FETCH_ASSOC)) {
            if ($previous === null) {
                $previous = $row[$primaryKey];
            }

            if ($previous !== $row[$primaryKey]) {
                $this->lastRow = $row;
                break;
            }
            $data[] = $row;
        }
        if ($data === null) {
            return null;
        }

        $items = $this->buildGraph($data) ?? [];
        foreach ($items as $item) {
            yield $item;
        }
        return null;
    }

    private function executeQuery(string $query, array $params = []): PDOStatement
    {
        $db = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $db->bindValue(':' . $key, $value);
            } else {
                $db->bindValue($key + 1, $value);
            }
        }
        $db->execute();
        return $db;
    }

    private function buildGraph(array $data): array
    {
        $tables[$this->firstAlias] = new Table($this->firstTable, $this->firstAlias);
        foreach ($this->joins as $alias => $join) {
            $tables[$alias] = new Table($join['to'], $alias, $join['one_to_many'], $join['key_relation'], $join['foreign_key']);
        }
        foreach ($tables as $alias => $table) {
            foreach ($this->joins as $join) {
                if ($join['alias'] !== $alias) {
                    continue;
                }
                /** @var array<Table> $parents */
                $parents = array_values(array_filter($tables, function ($table) use ($join) {
                    return $table->getTable() === $join['from'] || $join['from'] === $table->getAlias();
                }));

                foreach (array_unique($parents) as $parent) {
                    $parent->addChild($table);
                }
            }
        }

        if ($data === []) {
            return [];
        }

        return (new GraphBuilder($data, $tables, $this->primaryKey))->buildGraph();
    }

    private static function resolveColumns(string $aliasTable, array &$columns): void
    {
        foreach ($columns as $key => &$column) {
            $column = $aliasTable . '.' . $column;
            $alias = $column;
            if (is_string($key)) {
                $column = $aliasTable . '.' . $key;
            }
            $column = sprintf('%s AS %s', $column, str_replace('.', '__', str_replace('`', '', $alias)));
        }
        $columns = array_values($columns);
    }
}
