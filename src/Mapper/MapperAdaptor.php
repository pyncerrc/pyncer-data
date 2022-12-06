<?php
namespace Pyncer\Data\Mapper;

use Pyncer\Data\Mapper\MapperMapperInterface;
use Pyncer\Data\Model\ModelInterface;

use function array_key_exists;

class MapperAdaptor implements MapperAdaptorInterface
{
    public function __construct(
        protected MapperInterface $mapper,
        protected array $keys = []
    ) {}

    public function getMapper(): MapperInterface
    {
        return $this->mapper;
    }

    public function forgeModel(iterable $data = []): ModelInterface
    {
        return $this->getMapper()->forgeModel($this->getData($data));
    }

    public function hasKey(string $key): bool
    {
        return array_key_exists($key, $this->keys);
    }

    public function getKey(string $key): string
    {
        return $this->keys[$key] ?? $key;
    }

    public function getData(iterable $data): array
    {
        $newData = [];

        foreach ($data as $key => $value) {
            $newData[$this->getKey($key)] = $value;
        }

        return $newData;
    }
}
