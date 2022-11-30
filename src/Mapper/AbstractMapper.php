<?php
namespace Pyncer\Data\Mapper;

use DateTime;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Mapper\MapperResult;
use Pyncer\Data\Mapper\MapperResultInterface;
use Pyncer\Data\Mapper\Query\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\ConnectionTrait;
use Pyncer\Database\QueryResultInterface;
use Pyncer\Database\Query\SelectQueryInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\LogicException;
use Traversable;

use function array_map;
use function call_user_func;
use function intval;
use function is_int;
use function iterator_to_array;
use function preg_replace;
use function Pyncer\date_time as pyncer_date_time;
use function substr;

use const Pyncer\DATE_TIME_FORMAT as PYNCER_DATE_TIME_FORMAT;
use const Pyncer\DATE_FORMAT as PYNCER_DATE_FORMAT;
use const Pyncer\TIME_FORMAT as PYNCER_TIME_FORMAT;

abstract class AbstractMapper implements MapperInterface
{
    use ConnectionTrait;

    public function __construct(ConnectionInterface $connection)
    {
        $this->setConnection($connection);
    }

    abstract protected function getTable(): string;
    abstract public function forgeModel(iterable $data = []): ModelInterface;
    abstract public function isValidModel(ModelInterface $model): bool;
    public function isValidMapperQuery(MapperQueryInterface $mapperQuery): bool
    {
        return ($mapperQuery === null);
    }

    public function forgeResult(
        QueryResultInterface $result,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface
    {
        if ($mapperQuery !== null && !$this->isValidMapperQuery($mapperQuery)) {
            throw new InvalidArgumentException('Mapper query is invalid.');
        }

        return new MapperResult(
            $this,
            $result,
            $mapperQuery
        );
    }

    public function forgeModelFromResult(
        QueryResultInterface $result,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface
    {
        if ($mapperQuery !== null && !$this->isValidMapperQuery($mapperQuery)) {
            throw new InvalidArgumentException('Mapper query is invalid.');
        }

        if (!$result->valid()) {
            return null;
        }

        $data = $result->current();

        return $this->forgeModelFromData($data, $mapperQuery);
    }

    protected function forgeModelFromData(
        ?array $data,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface
    {
        if ($data === null) {
            return null;
        }

        $data = $this->unformatData($data);

        $model = $this->forgeModel($data);

        if ($mapperQuery !== null) {
            $model = $mapperQuery->overrideModel($this->getConnection(), $model, $data);
        }

        return $model;
    }

    public function selectById(
        int $id,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface
    {
        return $this->selectByColumns(['id' => $id], $mapperQuery);
    }

    public function selectByColumns(
        iterable $columns,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface
    {
        $row = $this->forgeSelectQuery($mapperQuery)
            ->where($columns)
            ->row();

        return $this->forgeModelFromData($row, $mapperQuery);
    }

    public function selectByQuery(
        callable $overrideQuery,
        ?MapperQueryInterface $mapperQuery = null
    ): ?ModelInterface
    {
        $query = $this->forgeSelectQuery($mapperQuery);

        call_user_func($overrideQuery, $query);

        $row = $query->row();

        return $this->forgeModelFromData($row, $mapperQuery);
    }

    public function selectByMapperQuery(
        MapperQueryInterface $mapperQuery
    ): ?ModelInterface
    {
        return $this->selectByColumns([], $mapperQuery);
    }

    public function selectAll(
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface
    {
        $result = $this->forgeSelectQuery($mapperQuery)
            ->result(['count' => 500]);

        return $this->forgeResult($result, $mapperQuery);
    }

    public function selectAllByColumns(
        iterable $columns,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface
    {
        $result = $this->forgeSelectQuery($mapperQuery)
            ->where($columns)
            ->result(['count' => 500]);

        return $this->forgeResult($result, $mapperQuery);
    }

    public function selectAllByQuery(
        callable $overrideQuery,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface
    {
        $query = $this->forgeSelectQuery($mapperQuery);

        call_user_func($overrideQuery, $query);

        $result = $query->result(['count' => 500]);

        return $this->forgeResult($result, $mapperQuery);
    }

    public function selectAllByMapperQuery(
        MapperQueryInterface $mapperQuery
    ): MapperResultInterface
    {
        return $this->selectAllByColumns([], $mapperQuery);
    }

    public function selectIndexed(
        int $count,
        int $offset = 0,
        ?MapperQueryInterface $mapperQuery = null
    ): MapperResultInterface
    {
        $result = $this->forgeSelectQuery($mapperQuery)
            ->limit($count, $offset)
            ->result();

        return $this->forgeResult($result, $mapperQuery);
    }

    public function selectNumRows(
        ?MapperQueryInterface $mapperQuery = null
    ): int
    {
        $query = $this->forgeSelectQuery($mapperQuery);

        return $query->numRows();
    }

    public function insert(ModelInterface $model): bool
    {
        if (!$this->isValidModel($model)) {
            throw new InvalidArgumentException('Model is invalid.');
        }

        $data = $model->getData();
        $data = $this->formatData($data);

        if ($model->getId() === 0) {
            unset($data['id']);
        }

        $result = $this->getConnection()
            ->insert($this->getTable())
            ->values($data)
            ->execute();

        if ($model->getId() === 0) {
            $id = $this->getConnection()->insertId();
            $model->setId($id);
        }

        return $result;
    }
    public function update(ModelInterface $model): bool
    {
        if (!$this->isValidModel($model)) {
            throw new InvalidArgumentException('Model is invalid.');
        }

        if (!$model->getId()) {
            throw new InvalidArgumentException('Model id must be greater than zero.');
        }

        $data = $model->getData();
        $data = $this->formatData($data);

        return $this->getConnection()
            ->update($this->getTable())
            ->values($data)
            ->where([
                'id' => $model->getId()
            ])
            ->execute();
    }
    public function replace(ModelInterface $model): bool
    {
        if ($model->getId() === 0) {
            return $this->insert($model);
        }

        return $this->update($model);
    }

    public function delete(ModelInterface $model): bool
    {
        if (!$this->isValidModel($model)) {
            throw new InvalidArgumentException('Model is invalid.');
        }

        return ($this->deleteById($model->getId()) ? true : false);
    }

    public function deleteById(int $id): bool
    {
        return ($this->deleteAllByIds([$id]) ? true : false);
    }

    public function deleteAllByIds(iterable $ids): int
    {
        if ($ids instanceof Traversable) {
            $ids = iterator_to_array($ids, false);
        }

        $ids = array_map('intval', $ids);
        foreach ($ids as $id) {
            if ($id <= 0) {
                throw new InvalidArgumentException('Ids must be greater than zero.');
            }
        }

        return $this->deleteAllByColumns([
            'id' => [$ids]
        ]);
    }

    public function deleteAllByColumns(iterable $columns): int
    {
        $this->getConnection()->delete($this->getTable())
            ->where($columns)
            ->execute();

        return $this->connection->affectedRows();
    }

    public function deleteAllByQuery(callable $overrideQuery): int
    {
        $query = $this->getConnection()->delete($this->getTable());

        call_user_func($overrideQuery, $query);

        $query->execute();

        return $this->connection->affectedRows();
    }

    protected function forgeSelectQuery(
        ?MapperQueryInterface $mapperQuery = null
    ): SelectQueryInterface
    {
        if ($mapperQuery !== null && !$this->isValidMapperQuery($mapperQuery)) {
            throw new InvalidArgumentException('Mapper query is invalid.');
        }

        $query = $this->getConnection()->select($this->getTable());

        if ($mapperQuery === null) {
            $this->overrideQuery($query);
        } else {
            $mapperQuery->overrideQuery($query);
        }

        //echo strval($query)."<br>\n<br>\n";

        return $query;
    }

    protected function overrideQuery(
        SelectQueryInterface $query
    ): SelectQueryInterface
    {
        $query->orderBy(null);
        return $query;
    }

    protected function formatData(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = json_encode($value);
            } elseif (substr($key, -10) === '_date_time') {
                $data[$key] = $this->getConnection()->dateTime($value);
            } elseif (substr($key, -5) === '_date') {
                $data[$key] = $this->getConnection()->date($value);
            } elseif (substr($key, -5) === '_time') {
                $data[$key] = $this->getConnection()->time($value);
            } elseif (substr($key, -16) === '_date_time_local') {
                $data[$key] = $this->getConnection()->dateTime($value, true);
            } elseif (substr($key, -11) === '_date_local') {
                $data[$key] = $this->getConnection()->date($value, true);
            } elseif (substr($key, -11) === '_time_local') {
                $data[$key] = $this->getConnection()->time($value, true);
            }
        }

        return $data;
    }

    protected function unformatData(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (substr($key, -10) === '_date_time') {
                $value = pyncer_date_time($value);
                $data[$key] = $value->format(PYNCER_DATE_TIME_FORMAT);
            } elseif (substr($key, -5) === '_date') {
                $value = pyncer_date_time($value);
                $data[$key] = $value->format(PYNCER_DATE_FORMAT);
            } elseif (substr($key, -5) === '_time') {
                $value = pyncer_date_time($value);
                $data[$key] = $value->format(PYNCER_TIME_FORMAT);
            } elseif (substr($key, -16) === '_date_time_local') {
                $value = pyncer_date_time($value, true);
                $data[$key] = $value->format(PYNCER_DATE_TIME_FORMAT);
            } elseif (substr($key, -11) === '_date_local') {
                $value = pyncer_date_time($value, true);
                $data[$key] = $value->format(PYNCER_DATE_FORMAT);
            } elseif (substr($key, -11) === '_time_local') {
                $value = pyncer_date_time($value, true);
                $data[$key] = $value->format(PYNCER_TIME_FORMAT);
            }
        }

        return $data;
    }
}
