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
        return implode(',', $this->getParts());
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
        parent::clean($validate, $reset);

        if ($this->cleanParts !== null) {
            $this->cleanParts = array_unique($this->cleanParts);
        }
    }
}
