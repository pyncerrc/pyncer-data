<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\RelationMapperInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Exception\InvalidArgumentException;

use function boolval;

abstract class AbstractRelationMapper implements RelationMapperInterface
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    abstract protected function getTable(): string;
    abstract protected function getParentIdColumn(): string;
    abstract protected function getChildIdColumn(): string;

    public function selectById(int $parentId, int $childId): bool
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        if ($childId <= 0) {
            throw new InvalidArgumentException('Child id must be greater than zero.');
        }

        $exists = $this->connection
            ->select($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId,
                $this->getChildIdColumn() => $childId
            ])
            ->limit(1)
            ->execute()
            ->getRow();

        return boolval($exists);
    }

    public function selectAll($parentId): array
    {
        $parentId = intval($parentId);
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        $result = $this->connection
            ->select($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId
            ])
            ->orderBy($this->getChildIdColumn())
            ->execute();

        $ids = [];

        foreach ($result as $row) {
            $ids[] = intval($row[$this->getChildIdColumn()]);
        }

        return $ids;
    }

    public function selectNumRows(int $parentId): int
    {
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        return $this->connection
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

        $result = $this->connection
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

        $this->connection->start();

        foreach ($childIds as $childId) {
            if (!$this->insert($parentId, $childId)) {
                $this->connection->rollback();
                return false;
            }
        }

        $this->connection->commit();

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

        $this->connection->start();

        $this->deleteAll($parentId);

        foreach ($childIds as $childId) {
            if (!$this->insert($parentId, $childId)) {
                $this->connection->rollback();
                return false;
            }
        }

        $this->connection->commit();

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

        $this->connection
            ->delete($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId,
                $this->getChildIdColumn() => $childId
            ])
            ->execute();

        return ($this->connection->affectedRows() ? true : false);
    }

    public function deleteAll($parentId): int
    {
        $parentId = intval($parentId);
        if ($parentId <= 0) {
            throw new InvalidArgumentException('Parent id must be greater than zero.');
        }

        $this->connection
            ->delete($this->getTable())
            ->where([
                $this->getParentIdColumn() => $parentId
            ])
            ->execute();

        return $this->connection->affectedRows();
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

        $this->connection
            ->delete($this->getTable())
            ->where([
                $this->getChildIdColumn() => $parentIds
            ])
            ->execute();

        return $this->connection->affectedRows();
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

        $this->connection
            ->delete($this->getTable())
            ->where([
                $this->getChildIdColumn() => $childIds
            ])
            ->execute();

        return $this->connection->affectedRows();
    }
}
