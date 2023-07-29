<?php
namespace Pyncer\Data\Tree;

use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Tree\DataTreeInterface;
use Pyncer\Data\Tree\TreeInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Iterable\Map;

use function array_key_exists;
use function in_array;

abstract class AbstractTree implements TreeInterface
{
    protected ConnectionInterface $connection;
    protected bool $preload;
    protected $items;
    protected ?MapperQueryInterface $mapperQuery;

    public function __construct(
        ConnectionInterface $connection,
        bool $preload = false
    ) {
        $this->connection = $connection;
        $this->preload = $preload;
        $this->items = null;
        $this->mapperQuery = null;
    }

    public function getMapperQuery(): ?MapperQueryInterface
    {
        return $this->mapperQuery;
    }
    public function setMapperQuery(?MapperQueryInterface $mapperQuery): static
    {
        $this->mapperQuery = $mapperQuery;
        return $this;
    }

    abstract protected function forgeMapper(): MapperInterface;

    protected function forgeMapperQuery(): ?MapperQueryInterface
    {
        return $this->mapperQuery;
    }

    public function getParents(?int $id, ?int $parentId = null): iterable
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero or null.');
        }

        $map = new Map();

        if ($id === null || $id === $parentId) {
            return $map;
        }

        $model = $this->getItem($id);
        $id = $model->getParentId();

        while (true) {
            if (!$id || $id === $parentId) {
                break;
            }

            $model = $this->getItem($id);

            $map->set($model->getId(), $model);

            $id = $model->getParentId();
        }

        $map->reverse();

        return $map;
    }
    public function hasParent(?int $id, int $parentId): bool
    {
        $ids = $this->getParents($id)->getKeys();

        return in_array($parentId, $ids, true);
    }

    public function getChildren(?int $id): iterable
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero or null.');
        }

        $map = new Map();

        if ($this->preload) {
            foreach ($this->getItems() as $model) {
                if ($model->getParentId() === $id) {
                    $map->set($model->getId(), $model);
                }
            }

            return $map;
        }

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $result = $mapper->selectAllByColumns([
            'parent_id' => $id
        ], $mapperQuery);

        foreach ($result as $model) {
            $this->addItem($model);
            $map->set($model->getId(), $model);
        }

        return $map;
    }
    public function getDescendents(?int $id): iterable
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero or null.');
        }

        $map = new Map();

        if ($this->preload) {
            foreach ($this->getItems() as $model) {
                if ($model->getParentId() === $id) {
                    $map->set($model->getId(), $model);

                    foreach ($this->getDescendents($model->getId()) as $model2) {
                        $map->set($model2->getId(), $model2);
                    }
                }
            }

            return $map;
        }

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $result = $mapper->selectAllByColumns([
            'parent_id' => $id
        ], $mapperQuery);

        foreach ($result as $model) {
            $this->addItem($model);
            $map->set($model->getId(), $model);

            foreach ($this->getDescendents($model->getId()) as $model2) {
                $map->set($model2->getId(), $model2);
            }
        }

        return $map;
    }

    public function getItems(): iterable
    {
        $this->preloadItems();

        return $this->items;
    }
    protected function preloadItems(): void
    {
        if (!$this->preload) {
            if ($this->items === null) {
                $this->items = [];
            }
            return;
        }

        if ($this->items !== null) {
            return;
        }

        $this->items = [];

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $result = $mapper->selectAll($mapperQuery);

        foreach ($result as $model) {
            $this->addItem($model);
        }
    }
    protected function addItem(ModelInterface $model): static
    {
        $this->items[$model->getId()] = $model;
        return $this;
    }

    public function hasItem(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero.');
        }

        $this->preloadItems();

        if (array_key_exists($id, $this->items)) {
            return true;
        }

        if ($this->preload) {
            return false;
        }

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $model = $mapper->selectById($id, $mapperQuery);

        if (!$model) {
            return false;
        }

        $this->addItem($model);

        return true;
    }
    public function getItem(int $id): ModelInterface
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero.');
        }

        $this->preloadItems();

        if (array_key_exists($id, $this->items)) {
            return $this->items[$id];
        }

        // If all the data is already loaded
        if ($this->preload) {
            throw new InvalidArgumentException('Id is invalid. (' . $id . ')');
        }

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $model = $mapper->selectById($id, $mapperQuery);

        if (!$model) {
            throw new InvalidArgumentException('Id is invalid. (' . $id . ')');
        }

        $this->addItem($model);

        return $this->items[$id];
    }
}
