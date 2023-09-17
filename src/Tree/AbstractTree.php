<?php
namespace Pyncer\Data\Tree;

use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Tree\DataTreeInterface;
use Pyncer\Data\Tree\TreeInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\UnexpectedValueException;

use function array_key_exists;
use function in_array;

abstract class AbstractTree implements TreeInterface
{
    /** @var array<int, ModelInterface> **/
    protected array $items = [];
    protected ?MapperQueryInterface $mapperQuery = null;
    protected bool $hasPreloaded = false;

    public function __construct(
        protected ConnectionInterface $connection,
        protected bool $preload = false,
        protected string $parentIdColumn = 'parent_id',
    ) {}

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

        $items = [];

        if ($id === null || $id === $parentId) {
            return $items;
        }

        $model = $this->getItem($id);

        $id = $model[$this->parentIdColumn];

        if ($id !== null && !is_int($id)) {
            throw new UnexpectedValueException('Parent id column returned an invalid value.');
        }

        while (true) {
            if (!$id || $id === $parentId) {
                break;
            }

            $model = $this->getItem($id);

            $items[$model->getId()] = $model;

            $id = $model[$this->parentIdColumn];

            if ($id !== null && !is_int($id)) {
                throw new UnexpectedValueException('Parent id column returned an invalid value.');
            }
        }

        $items = array_reverse($items, true);

        return $items;
    }
    public function hasParent(?int $id, int $parentId): bool
    {
        $ids = $this->getParents($id);

        $ids = array_keys([...$ids]);

        return in_array($parentId, $ids, true);
    }

    public function getChildren(?int $id): iterable
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero or null.');
        }

        $items = [];

        if ($this->preload) {
            foreach ($this->getItems() as $model) {
                $parentId = $model[$this->parentIdColumn];

                if ($parentId !== null && !is_int($parentId)) {
                    throw new UnexpectedValueException('Parent id column returned an invalid value.');
                }

                if ($parentId === $id) {
                    $items[$model->getId()] = $model;
                }
            }

            return $items;
        }

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $result = $mapper->selectAllByColumns([
            'parent_id' => $id
        ], $mapperQuery);

        foreach ($result as $model) {
            $this->addItem($model);
            $items[$model->getId()] = $model;
        }

        return $items;
    }
    public function getDescendents(?int $id): iterable
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('Id must be greater than zero or null.');
        }

        $items = [];

        if ($this->preload) {
            foreach ($this->getItems() as $model) {
                $parentId = $model[$this->parentIdColumn];

                if ($parentId !== null && !is_int($parentId)) {
                    throw new UnexpectedValueException('Parent id column returned an invalid value.');
                }

                if ($parentId === $id) {
                    $items[$model->getId()] = $model;

                    foreach ($this->getDescendents($model->getId()) as $model2) {
                        $items[$model2->getId()] = $model2;
                    }
                }
            }

            return $items;
        }

        $mapper = $this->forgeMapper();
        $mapperQuery = $this->forgeMapperQuery();
        $result = $mapper->selectAllByColumns([
            'parent_id' => $id
        ], $mapperQuery);

        foreach ($result as $model) {
            $this->addItem($model);
            $items[$model->getId()] = $model;

            foreach ($this->getDescendents($model->getId()) as $model2) {
                $items[$model2->getId()] = $model2;
            }
        }

        return $items;
    }

    public function getItems(): iterable
    {
        $this->preloadItems();

        return $this->items;
    }
    protected function preloadItems(): void
    {
        if (!$this->preload) {
            return;
        }

        if ($this->hasPreloaded) {
            return;
        }

        $this->hasPreloaded = true;
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
