<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Data\MapperQuery\AbstractQueryParam;

use function call_user_func;
use function in_array;
use function Pyncer\Array\data_explode as pyncer_array_data_explode;

class OptionsQueryParam extends AbstractQueryParam
{
    private ?array $cleanedParts = null;

    public function setQueryParamString(string $value): static
    {
        $this->cleanedParts = null;
        return parent::setQueryParamString($value);
    }

    public function addQueryParamString(string $value): static
    {
        if ($this->getQueryParamString === '') {
            $this->setQueryParamString($value);
        } else {
            $this->setQueryParamString(
                $this->queryParamString . ',' . $value
            );
        }

        return $this;
    }

    public function getParts(): array
    {
        return $this->cleanedParts ?? parent::getParts();
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
            $this->cleanedParts = null;
        }

        $newParts = [];

        $parts = array_unique($this->getParts());

        foreach ($parts as $value) {
            if (call_user_func($validate, $value)) {
                $newParts[] = $value;
            }
        }

        $this->cleanedParts = $newParts;
    }
}
