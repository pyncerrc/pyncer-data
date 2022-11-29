<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Mapper\MapperResultInterface;
use Pyncer\Data\Mapper\Query\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\QueryResultInterface;

use function array_key_exists;
use function array_map;
use function Pyncer\Array\intersect_keys as pyncer_array_intersect_keys;
use function Pyncer\Array\set_recursive as pyncer_array_set_recursive;

class MapperResult implements MapperResultInterface
{
    private MapperInterface $mapper;
    private QueryResultInterface $result;
    private ?MapperQueryInterface $mapperQuery;
    private array $models;

    public function __construct(
        MapperInterface $mapper,
        QueryResultInterface $result,
        ?MapperQueryInterface $mapperQuery = null)
    {
        $this->mapper = $mapper;
        $this->result = $result;
        $this->mapperQuery = $mapperQuery;
        $this->models = [];
    }

    public function rewind(): void
    {
        $this->result->rewind();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if (!array_key_exists($this->key(), $this->models)) {
            $this->models[$this->key()] = $this->mapper->forgeModelFromResult(
                $this->result,
                $this->mapperQuery
            );
        }

        return $this->models[$this->key()];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->result->key();
    }

    public function next(): void
    {
        $this->result->next();
    }

    public function valid(): bool
    {
        return $this->result->valid();
    }

    public function count(): int
    {
        return $this->result->count();
    }

    public function getData(): array
    {
        $data = [];

        foreach ($this as $value) {
            $data[] = $value->getData();
        }

        return $data;
    }
    public function getAllData(): array
    {
        $data = [];

        foreach ($this as $value) {
            $data[] = $value->getAllData();
        }

        return $data;
    }

    public function getRow(): ?ModelInterface
    {
        $this->result->rewind();
        return $this->current();
    }
    public function getRows(string ...$keys): array
    {
        $data = [];

        if ($keys) {
            foreach ($this as $value) {
                $actualKeys = array_map(function($key) use($value) {
                    return $value->get($key) ?? '@';
                }, $keys);
                $data = pyncer_array_set_recursive($data, $actualKeys, $value);
                //$data[$value->get($key)] = $value;
            }
        } else {
            foreach ($this as $value) {
                $data[] = $value;
            }
        }

        return $data;
    }
    public function getColumn(string $column, string ...$keys): array
    {
        $data = [];

        if ($keys) {
            foreach ($this as $value) {
                $actualKeys = array_map(function($key) use($value) {
                    return $value->get($key) ?? '@';
                }, $keys);
                $data = pyncer_array_set_recursive($data, $actualKeys, $value->get($column));
                //$data[$value->get($keys)] = $value->get($column);
            }
        } else {
            foreach ($this as $value) {
                $data[] = $value->get($column);
            }
        }

        return $data;
    }
    public function getColumns(array $columns, string ...$keys): array
    {
        $data = [];

        if ($keys) {
            foreach ($this as $value) {
                $actualKeys = array_map(function($key) use($value) {
                    return $value->get($key) ?? '@';
                }, $keys);
                $data = pyncer_array_set_recursive(
                    $data,
                    $actualKeys,
                    pyncer_array_intersect_keys($value->getData(), $columns)
                );
                //$data[$value->get($keys)] = $value->get($column);
            }
        } else {
            foreach ($this as $value) {
                $data[] = pyncer_array_intersect_keys($value->getData(), $columns);
            }
        }

        return $data;
    }
}
