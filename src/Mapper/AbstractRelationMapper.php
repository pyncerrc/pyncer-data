<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Mapper\RelationMapperInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\ConnectionTrait;
use Pyncer\Exception\InvalidArgumentException;

use function boolval;

abstract class AbstractRelationMapper implements RelationMapperInterface
{
    use ConnectionTrait;

    public function __construct(ConnectionInterface $connection)
    {
        $this->setConnection($connection);
    }

    abstract public function getTable(): string;
    abstract public function getParentIdColumn(): string;
    abstract public function getChildIdColumn(): string;

    public function selectById(int $parentId, int $childId): bool
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        if ($childId <= 0) {
            throw new InvalidArgumentException('Child id must be greater than zero.');
        }

        $exists = $this->getConnection()
            ->select($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId,
                $this->getChildIdColumn() => $childId
            ])
            ->limit(1)
            ->row();

        return boolval($exists);
    }

    public function selectAll(int $parentId): array
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        /** @var object **/
        $result = $this->getConnection()
            ->select($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId
            ])
            ->orderBy($this->getChildIdColumn())
            ->execute();

        $ids = [];

        while ($row = $this->getConnection()->fetch($result)) {
            $ids[] = intval($row[$this->getChildIdColumn()]);
        }

        return $ids;
    }

    public function selectNumRows(int $parentId): int
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        return $this->getConnection()
            ->select($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId
            ])
            ->numRows();
    }

    public function insert(int $parentId, int $childId): bool
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        if ($childId <= 0) {
            throw new InvalidArgumentException('Child id must be greater than zero.');
        }

        if ($this->selectById($parentId, $childId)) {
            return true;
        }

        $result = $this->getConnection()
            ->insert($this->getTable())
            ->values([
                $this->getParentIdColumn() => $parentId,
                $this->getChildIdColumn() => $childId
            ])
            ->execute();

        return boolval($result);
    }

    public function insertAll(int $parentId, array $childIds): bool
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        if (!$childIds) {
            return true;
        }

        $childIds = array_map('intval', $childIds);
        foreach ($childIds as $childId) {
            if ($childId <= 0) {
                throw new InvalidArgumentException('Child ids must be greater than zero.');
            }
        }

        $this->getConnection()->start();

        foreach ($childIds as $childId) {
            if (!$this->insert($parentId, $childId)) {
                $this->getConnection()->rollback();
                return false;
            }
        }

        $this->getConnection()->commit();

        return true;
    }
    public function updateAll(int $parentId, array $childIds): bool
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        $childIds = array_map('intval', $childIds);
        foreach ($childIds as $childId) {
            if ($childId <= 0) {
                throw new InvalidArgumentException('Child ids must be greater than zero.');
            }
        }

        $this->getConnection()->start();

        $this->deleteAll($parentId);

        foreach ($childIds as $childId) {
            if (!$this->insert($parentId, $childId)) {
                $this->getConnection()->rollback();
                return false;
            }
        }

        $this->getConnection()->commit();

        return true;
    }

    public function delete(int $parentId, int $childId): bool
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        if ($childId <= 0) {
            throw new InvalidArgumentException('Child id must be greater than zero.');
        }

        $this->getConnection()
            ->delete($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId,
                $this->getChildIdColumn() => $childId
            ])
            ->execute();

        return ($this->getConnection()->affectedRows() ? true : false);
    }

    public function deleteAll(int $parentId): int
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        $this->getConnection()
            ->delete($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId
            ])
            ->execute();

        return $this->getConnection()->affectedRows();
    }

    public function deleteAllParents(array $parentIds): int
    {
        if (!$parentIds) {
            return 0;
        }

        $parentIds = array_map('intval', $parentIds);
        foreach ($parentIds as $parentId) {
            if ($parentId <= 0) {
                throw new InvalidArgumentException('Parent ids must be greater than zero.');
            }
        }

        $this->getConnection()
            ->delete($this->getTable())
            ->where([
                $this->getChildIdColumn() => $parentIds
            ])
            ->execute();

        return $this->getConnection()->affectedRows();
    }

    public function deleteAllChildren(array $childIds): int
    {
        if (!$childIds) {
            return 0;
        }

        $childIds = array_map('intval', $childIds);
        foreach ($childIds as $childId) {
            if ($childId <= 0) {
                throw new InvalidArgumentException('Child ids must be greater than zero.');
            }
        }

        $this->getConnection()
            ->delete($this->getTable())
            ->where([
                $this->getChildIdColumn() => $childIds
            ])
            ->execute();

        return $this->getConnection()->affectedRows();
    }
}
