<?php
namespace Pyncer\Data\MapperQuery;

use function trim;

abstract class AbstractQueryParam
{
    protected string $queryParamString;
    private ?array $parts = null;

    public function __construct(string $queryParamString)
    {
        $this->setQueryParamString($queryParamString);
    }

    public function getQueryParamString(): string
    {
        return $this->queryParamString;
    }
    public function setQueryParamString(string $value): static
    {
        $this->queryParamString = trim($value);
        $this->parts = null;

        return $this;
    }

    abstract public function addQueryParamString(string $value): static;

    public function getParts(): array
    {
        if ($this->parts === null) {
            $this->parts = $this->parseQueryParam(
                $this->getQueryParamString()
            );
        }

        return $this->parts;
    }

    abstract protected function parseQueryParam(string $queryParamString): array;
    abstract public function clean(callable $validate, bool $reset = false): void;

    public function __toString(): string
    {
        return $this->getQueryParamString();
    }
}
