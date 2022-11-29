<?php
namespace Pyncer\Data\Mapper;

use Countable;
use Iterator;
use Pyncer\Data\Model\ModelInterface;

interface MapperResultInterface extends Iterator, Countable
{
    public function getData(): array;
    public function getAllData(): array;

    public function getRow(): ?ModelInterface;
    public function getRows(string ...$keys): array;
    public function getColumn(string $column, string ...$keys): array;
    public function getColumns(array $columns, string ...$keys): array;
}
