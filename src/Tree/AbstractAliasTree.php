<?php
namespace Pyncer\Data\Tree;

use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Tree\AbstractTree;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\OutOfBoundsException;
use Traversable;

abstract class AbstractAliasTree extends AbstractTree implements
    AliasTreeInterface
{
    /** @var array<string, ModelInterface> **/
    protected array $aliasItems = [];

    public function __construct(
        ConnectionInterface $connection,
        bool $preload = false,
        string $parentIdColumn = 'parent_id',
        protected string $aliasColumn = 'alias',
    ) {
        parent::__construct($connection, $preload, $parentIdColumn);
    }

    public function getAliases(?int $id, ?int $parentId = null): iterable
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero or null.');
        }

        if ($parentId !== null && $parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero or null.');
        }

        if ($id === null || $id === $parentId) {
            return [];
        }

        $map = $this->getParents($id, $parentId);

        $aliases = [];

        foreach ($map as $key => $item) {
            $aliases[] = $item->get($this->aliasColumn);
        }

        $aliases[] = $this->getItem($id)->get($this->aliasColumn);

        return $aliases;
    }

    public function getAliasPath(?int $id, ?int $parentId = null): string
    {
        $aliases = $this->getAliases($id, $parentId);

        return '/' . implode('/', [...$aliases]);
    }

    protected function addItem(ModelInterface $model): static
    {
        parent::addItem($model);

        $key = $model->get($this->parentIdColumn) . '|' .
            $model->get($this->aliasColumn);

        $this->aliasItems[$key] = $model;

        return $this;
    }

    public function hasItemFromAlias(
        string $alias,
        ?int $parentId = null
    ): bool
    {
        if ($parentId !== null && $parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero or null.');
        }

        return $this->hasItemFromAliasAndColumns($alias, [
            'parent_id' => $parentId
        ]);
    }

    public function getItemFromAlias(
        string $alias,
        ?int $parentId = null
    ): ModelInterface
    {
        if ($parentId !== null && $parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero or null.');
        }

        return $this->getItemFromAliasAndColumns($alias, [
            'parent_id' => $parentId
        ]);
    }

    public function hasItemFromAliasPath(
        string $path,
        ?int $parentId = null
    ): bool
    {
        if ($parentId !== null && $parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero or null.');
        }

        return $this->hasItemFromAliasPathAndColumns($path, [
            'parent_id' => $parentId
        ]);
    }

    public function getItemFromAliasPath(
        string $path,
        ?int $parentId = null
    ): ModelInterface
    {
        if ($parentId !== null && $parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero or null.');
        }

        return $this->getItemFromAliasPathAndColumns($path, [
            'parent_id' => $parentId
        ]);
    }

    protected function hasItemFromAliasAndColumns(
        string $alias,
        ?iterable $columns = null
    ): bool
    {
        if ($alias === '') {
            throw new InvalidArgumentException('Alias cannot be empty.');
        }

        $this->preloadItems();

        $columns = $this->cleanColumns($columns);

        $key = $columns['parent_id'] . '|' . $alias;

        if (array_key_exists($key, $this->aliasItems)) {
            return true;
        }

        // If all the data is already loaded
        if ($this->preload) {
            return false;
        }

        $columns['alias'] = $alias;

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $model = $mapper->selectByColumns($columns, $mapperQuery);

        if (!$model) {
            return false;
        }

        $this->addItem($model);

        return true;
    }

    protected function getItemFromAliasAndColumns(
        string $alias,
        ?iterable $columns = null
    ): ModelInterface
    {
        if ($alias === '') {
            throw new InvalidArgumentException('Alias cannot be empty.');
        }

        $this->preloadItems();

        $columns = $this->cleanColumns($columns);

        $key = $columns['parent_id'] . '|' . $alias;

        if (array_key_exists($key, $this->aliasItems)) {
            return $this->aliasItems[$columns['parent_id'] . '|' . $alias];
        }

        // If all the data is already loaded
        if ($this->preload) {
            throw new OutOfBoundsException('Item not found. (' . $alias . ')');
        }

        $columns['alias'] = $alias;

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $model = $mapper->selectByColumns($columns, $mapperQuery);

        if (!$model) {
            throw new OutOfBoundsException('Item not found. (' . $alias . ')');
        }

        $this->addItem($model);

        $key = $model->get($this->parentIdColumn) . '|' . $alias;

        return $this->aliasItems[$key];
    }

    protected function hasItemFromAliasPathAndColumns(
        string $path,
        ?iterable $columns = null
    ): bool
    {
        $path = trim($path, '/');

        if ($path === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        $path = explode('/', $path);

        $model = null;

        $columns = $this->cleanColumns($columns);

        foreach ($path as $alias) {
            if (!$this->hasItemFromAliasAndColumns($alias, $columns)) {
                return false;
            }

            $model = $this->getItemFromAliasAndColumns($alias, $columns);
            $columns['parent_id'] = $model->getId();
        }

        return true;
    }

    protected function getItemFromAliasPathAndColumns(
        string $path,
        ?iterable $columns = null
    ): ModelInterface
    {
        $path = trim($path, '/');

        if ($path === '') {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        $path = explode('/', $path);

        $model = null;

        $columns = $this->cleanColumns($columns);

        foreach ($path as $alias) {
            $model = $this->getItemFromAliasAndColumns($alias, $columns);
            $columns['parent_id'] = $model->getId();
        }

        return $model;
    }

    private function cleanColumns(?iterable $columns): array
    {
        if ($columns === null) {
            $columns = [];
        } elseif ($columns instanceof Traversable) {
            $columns = iterator_to_array($columns, true);
        }

        if (!array_key_exists('parent_id', $columns)) {
            $columns['parent_id'] = null;
        }

        return $columns;
    }
}
