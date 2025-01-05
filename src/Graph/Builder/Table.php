<?php

namespace PhpDevCommunity\Sql\Graph\Builder;

final class Table
{
    private string $table;
    private string $alias;
    private ?Table $parent = null;

    /**
     * @var array<Table>
     */
    private array $children = [];

    private bool $oneToMany = false;
    private ?string $relationKey = null;
    private ?string $sourceForeignKey = null;

    public function __construct(
        string $table,
        string $alias,
        bool $isOneToMany = false,
        string $relationKey = null,
        string $sourceForeignKey = null
    )
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->oneToMany = $isOneToMany;
        $this->relationKey = $relationKey;
        $this->sourceForeignKey = $sourceForeignKey;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function isOneToMany(): bool
    {
        return $this->oneToMany;
    }

    public function getRelationKey(): ?string
    {
        return $this->relationKey ?? $this->getAlias();
    }

    public function getSourceForeignKey(): ?string
    {
        return $this->sourceForeignKey;
    }


    public function getParent(): ?Table
    {
        return $this->parent;
    }

    public function setParent(Table $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(Table $child): self
    {
        $child->setParent($this);
        $this->children[] = $child;
        return $this;
    }
}