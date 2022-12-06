<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Model\ModelInterface;

interface MapperAdaptorInterface
{
    public function getMapper(): MapperInterface;
    public function forgeModel(iterable $data = []): ModelInterface;
    public function hasKey(string $key): bool;
    public function getKey(string $key): string;
    public function getData(iterable $data): array;
}
