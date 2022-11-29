<?php
namespace Pyncer\Data;

use function trim;

abstract class AbstractQueryParam
{
    protected string $queryParamString;
    protected array $parts;

    public function __construct(string $queryParamString)
    {
        $this->setQueryParamString($queryParamString);
    }

    public function getQueryParamString(): string
    {
        return $this->queryParamString;
    }
    protected function setQueryParamString(string $value): void
    {
        $this->queryParamString = trim($value);
        $this->parts = $this->parseQueryParam($this->queryParamString);
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    abstract protected function parseQueryParam(string $queryParamString): array;
    abstract public function clean(callable $validate, bool $reset = false): void;

    public function __toString(): string
    {
        return $this->getQueryParamString();
    }
}
