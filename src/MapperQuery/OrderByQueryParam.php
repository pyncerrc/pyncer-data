<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Data\MapperQuery\AbstractQueryParam;
use Pyncer\Exception\InvalidArgumentException;

use function call_user_func_array;
use function count;
use function explode;
use function Pyncer\Array\data_explode as pyncer_array_data_explode;

class OrderByQueryParam extends AbstractQueryParam
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
    public function hasOrderBy(string $name): bool
    {
        foreach ($this->getParts as $part) {
            if ($part[0] === $name) {
                return true;
            }
        }

        return false;
    }
    public function getOrderBy(string $name): ?array
    {

        foreach ($this->getParts as $part) {
            if ($part[0] === $name) {
                return $part;
            }
        }

        return null;
    }

    protected function parseQueryParam(string $queryParamString): array
    {
        if ($queryParamString === '') {
            return [];
        }

        $orderByParts = [];

        $parts = pyncer_array_data_explode(',', $queryParamString);

        foreach ($parts as $value) {
            $value = explode(' ', $value);

            $len = count($value);

            if ($len > 2) {
                throw new InvalidArgumentException('Invalid order by value.');
            }

            if ($len == 2) {
                if ($value[1] !== 'desc' && $value[1] !== 'asc') {
                    throw new InvalidArgumentException('Invalid order by value.');
                }
            } else {
                $value[1] = 'asc';
            }

            $orderByParts[] = [$value[0], ($value[1] === 'desc' ? '<' : '>')];
        }

        return $orderByParts;
    }

    public function clean(callable $validate, bool $reset = false): void
    {
        if ($reset) {
            $this->cleanedParts = null;
        }

        $newParts = [];

        foreach ($this->getParts() as $value) {
            if (call_user_func_array($validate, $value)) {
                $newParts[] = $value;
            }
        }

        $this->cleanedParts = $newParts;
    }
}
