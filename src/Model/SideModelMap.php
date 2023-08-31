<?php
namespace Pyncer\Data\Model;

use Pyncer\Data\Mapper\MapperResultInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Iterable\Map;
use Stringable;

use function array_map;
use function is_scalar;
use function is_iterable;
use function is_array;
use function iterator_to_array;
use function strval;

class SideModelMap extends Map
{
    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): static
    {
        if ($value instanceof MapperResultInterface) {
            $value = iterator_to_array($value, false);
        }

        $this->values[$key] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return array_map($this->getDataInternal(...), $this->values);
    }

    /**
     * @internal
     */
    private function getDataInternal(mixed $data): mixed
    {
        if ($data === null) {
            return null;
        }

        if (is_scalar($data)) {
            return $data;
        }

        if ($data instanceof ModelInterface) {
            return $data->getAllData();
        }

        if ($data instanceof Stringable) {
            return strval($value);
        }

        if (is_iterable($value)) {
            $value = iterator_to_array($value, true);
        }

        if (is_array($value)) {
            return array_map($this->getDataInternal(...), $value);
        }

        return null;
    }
}
