<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Data\MapperQuery\AbstractQueryParam;

use function call_user_func;
use function in_array;
use function Pyncer\Array\data_explode as pyncer_array_data_explode;

class OptionsQueryParam extends AbstractQueryParam
{
    public function addQueryParamString(string $value): static
    {
        if ($this->getQueryParamString() === '') {
            $this->setQueryParamString($value);
        } else {
            $this->setQueryParamString(
                $this->getQueryParamString() . ',' . $value
            );
        }

        return $this;
    }

    public function getCleanQueryParamString(): string
    {
        return implode(', ', $this->getParts());
    }

    public function hasOption(string $option): bool
    {
        return in_array($option, $this->getParts());
    }

    protected function parseQueryParam(string $queryParamString): array
    {
        $parts = pyncer_array_data_explode(',', $queryParamString);
        return $parts;
    }

    public function clean(callable $validate, bool $reset = false): void
    {
        if ($reset) {
            $this->cleanParts = null;
        }

        $newParts = [];

        foreach ($this->getParts() as $value) {
            if (call_user_func($validate, $value)) {
                $newParts[] = $value;
            }
        }

        $this->cleanParts = array_unique($newParts);
    }
}
