<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Exception\InvalidArgumentException;

use function trim;

abstract class AbstractQueryParam implements QueryParamInterface
{
    protected string $queryParamString;
    private ?array $parts = null;
    protected ?array $cleanParts = null;

    public function __construct(string $queryParamString = '')
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

    public function getCleanQueryParamString(): string
    {
        return $this->buildQueryParamStringFromParts($this->getParts());
    }

    public function addQueryParam(QueryParamInterface $value): static
    {
        $class = static::class;
        if (!($value instanceof $class)) {
           throw new InvalidArgumentException('Query param must be an instance of the same class.');
        }

        $this->queryParamString = $this->mergeQueryParamStrings(
            $this->queryParamString,
            $value->getQueryParamString()
        );

        $this->parts = null;

        if ($this->hasCleaned() || $value->hasCleaned()) {
            $this->cleanParts = $this->mergeQueryParamParts(
                $this->getParts(),
                $value->getParts(),
            );
        } else {
            $this->cleanParts = null;
        }

        return $this;
    }

    public function addQueryParamString(string $value): static
    {
        if ($this->getQueryParamString() === '') {
            $this->setQueryParamString($value);
            return $this;
        }

        $this->queryParamString = $this->mergeQueryParamStrings(
            $this->queryParamString,
            $value
        );

        $this->parts = null;

        if ($this->hasCleaned()) {
            $this->cleanParts = $this->mergeQueryParamParts(
                $this->getParts(),
                $this->parseQueryParam($value),
            );
        } else {
            $this->cleanParts = null;
        }

        return $this;
    }

    abstract protected function mergeQueryParamStrings(
        string $queryParamString1,
        string $queryParamString2
    ): string;

    abstract protected function mergeQueryParamParts(
        array $queryParamParts1,
        array $queryParamParts2,
    ): array;

    abstract protected function buildQueryParamStringFromParts(
        array $queryParamParts,
    ): string;

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

    public function hasCleaned(): bool
    {
        return ($this->cleanParts !== null);
    }

    public function __toString(): string
    {
        return $this->getQueryParamString();
    }
}
