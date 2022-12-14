<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Formatting\FormatterInterface;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;

interface MapperAdaptorInterface
{
    public function getMapper(): MapperInterface;
    public function getMapperQuery(): ?MapperQueryInterface;
    public function getFormatter(): FormatterInterface;

    public function forgeModel(iterable $data): ModelInterface;
    public function forgeData(ModelInterface $model): array;
}
