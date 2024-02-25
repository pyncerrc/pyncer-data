<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Data\MapperQuery\FiltersQueryParam;
use Pyncer\Data\MapperQuery\OptionsQueryParam;
use Pyncer\Data\MapperQuery\OrderByQueryParam;

class QueryParams
{
    public function __construct(
        protected ?string $filters = null,
        protected ?string $options = null,
        protected ?string $orderBy = null,
        protected ?string $queryMode = null,
    ) {}

    public function getFilters(): ?FiltersQueryParam
    {
        if ($this->filters === null) {
            return null;
        }

        return new FitlersQueryParam($this->filters);
    }

    public function getOptions(): ?OptionsQueryParam
    {
        if ($this->options === null) {
            return null;
        }

        return new OptionsQueryParam($this->options);
    }

    public function getOrderBy(): ?OrderByQueryParam
    {
        if ($this->orderBy === null) {
            return null;
        }

        return new OrderByQueryParam($this->orderBy);
    }

    public function getQueryMode(): ?string
    {
        return $this->queryMode;
    }

    public function isEmpty(): bool
    {
        return (
            $this->filters === null &&
            $this->options === null &&
            $this->orderBy === null &&
            $this->queryMode === null
        );
    }
}
