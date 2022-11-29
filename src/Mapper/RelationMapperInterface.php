<?php
namespace Pyncer\Data\Mapper;

use Pyncer\App as pa;

interface RelationMapperInterface
{
    public function selectById(int $parentId, int $childId): bool;
    public function selectAll(int $parentId): array;
    public function selectNumRows(int $parentId): int;

    public function insert(int $parentId, int $childId): bool;
    public function insertAll(int $parentId, array $childIds): bool;
    public function updateAll(int $parentId, array $childIds): bool;

    public function delete(int $parentId, int $childId): bool;
    public function deleteAll(int $parentId): int;

    public function deleteAllParents(array $parentIds): int;
    public function deleteAllChildren(array $childIds): int;
}
