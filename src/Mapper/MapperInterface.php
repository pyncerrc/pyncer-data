<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Mapper\MapperResultInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\QueryResultInterface;

interface MapperInterface
{
    public function forgeModel(iterable $data = []): ModelInterface;

    public function isValidModel(ModelInterface $model): bool;

    public function forgeResult(
        QueryResultInterface $result,
        ?MapperQueryInterface
        $mapperQuery = null
    ): MapperResultInterface;

    public function forgeModelFromResult(
        QueryResultInterface $result,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface;

    public function selectById(
        int $id,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface;

    public function selectByColumns(
        iterable $columns,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface;

    public function selectByQuery(
        callable $overrideQuery,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface;

    public function selectByMapperQuery(
        MapperQueryInterface $mapperQuery
    ): ?ModelInterface;

    public function selectAll(
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface;

    public function selectAllByColumns(
        iterable $columns,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface;

    public function selectAllByQuery(
        callable $overrideQuery,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface;

    public function selectAllByMapperQuery(
        MapperQueryInterface $mapperQuery
    ): MapperResultInterface;

    public function selectIndexed(
        int $count,
        int $offset = 0,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface;

    public function selectNumRows(
        ?MapperQueryInterface $mapperQuery = null
    ): int;

    public function insert(ModelInterface $model): bool;
    public function update(ModelInterface $model): bool;
    public function replace(ModelInterface $model): bool;

    public function delete(ModelInterface $model): bool;
    public function deleteById(int $id): bool;

    public function deleteAllByIds(iterable $ids): int;
    public function deleteAllByColumns(iterable $columns): int;
    public function deleteAllByQuery(callable $overrideQuery): int;
}
