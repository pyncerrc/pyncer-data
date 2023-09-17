<?php
namespace Pyncer\Data\MapperQuery;

use function trim;

abstract class AbstractQueryParam
{
    protected string $queryParamString;
    private ?array $parts = null;
    protected ?array $cleanParts = null;

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
        $this->cleanParts = null;

        return $this;
    }

    abstract public function addQueryParamString(string $value): static;
    abstract public function getCleanQueryParamString(): string;

    public function getParts(): array
    {
        if ($this->cleanParts !== null) {
            return $this->cleanParts;
        }

        if ($this->parts === null) {
            $this->parts = $this->parseQueryParam(
                $this->getQueryParamString()
            );
        }

        return $this->parts;
    }

    abstract protected function parseQueryParam(string $queryParamString): array;

    /**
     * Cleans each value in the query param string.
     *
     * @param callable $validate A validate function to perform the cleaning.
     * @param bool $reset When true, any previous modifications as a result of
     *  calling clean will be discarted.
     */
    abstract public function clean(callable $validate, bool $reset = false): void;

    public function __toString(): string
    {
        return $this->getQueryParamString();
    }
}
