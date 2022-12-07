<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Formatting\FormatterInterface;
use Pyncer\Data\Formatting\VoidFormatter;
use Pyncer\Data\Mapper\MapperInterface;
use Pyncer\Data\Mapper\Query\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;

use function array_key_exists;

class MapperAdaptor implements MapperAdaptorInterface
{
    public function __construct(
        protected MapperInterface $mapper,
        protected ?MapperQueryInterface $mapperQuery = null,
        protected ?FormatterInterface $formatter = null,
    ) {
        if ($formatter === null) {
            $this->formatter = new VoidFormatter();
        }
    }

    public function getMapper(): MapperInterface
    {
        return $this->mapper;
    }

    public function getMapperQuery(): ?MapperQueryInterface
    {
        return $this->mapperQuery;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    public function forgeModel(iterable $data = []): ModelInterface
    {
        return $this->getMapper()->forgeModel(
            $this->formatter->formatData($data)
        );
    }

    public function forgeData(ModelInterface $model): array
    {
        return $this->formatter->unformatData($model->getData());
    }
}
