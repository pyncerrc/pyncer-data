<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Formatting\FormatterInterface;
use Pyncer\Data\Mapper\MapperMapperInterface;
use Pyncer\Data\Model\ModelInterface;

use function array_key_exists;

class MapperAdaptor implements MapperAdaptorInterface
{
    public function __construct(
        protected MapperInterface $mapper,
        protected FormatterInterface $formatter,
    ) {}

    public function getMapper(): MapperInterface
    {
        return $this->mapper;
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
