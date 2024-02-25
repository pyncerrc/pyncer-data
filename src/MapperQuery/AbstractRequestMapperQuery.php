<?php
namespace Pyncer\Data\MapperQuery;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Data\MapperQuery\AbstractMapperQuery;
use Pyncer\Data\MapperQuery\FiltersQueryParam;
use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\MapperQuery\OptionsQueryParam;
use Pyncer\Data\MapperQuery\OrderByQueryParam;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\RequestData;

use function array_merge;
use function array_values;
use function in_array;

abstract class AbstractRequestMapperQuery extends AbstractMapperQuery
{
    protected ?PsrServerRequestInterface $request;
    protected string $prefix;

    private ?string $queryMode = null;
    private bool $resetRequired = true;

    private ?FiltersQueryParam $filters = null;
    private ?OptionsQueryParam $options = null;
    private ?OrderByQueryParam $orderBy = null;

    public function __construct(
        ConnectionInterface $connection,
        ?PsrServerRequestInterface $request = null,
        string $prefix = ''
    ) {
        parent::__construct($connection);

        $this->request = $request;
        $this->prefix = $prefix;

        if ($request !== null) {
            $queryParams = RequestData::fromQueryParams($request);

            // Filters
            $queryParam = (
                $prefix === '' ?
                '$filters' :
                '$' . $prefix . 'Filters'
            );
            $filtersQueryParam = $queryParams->getString($queryParam, null);
            if ($filtersQueryParam !== null) {
                $filters = new FiltersQueryParam($filtersQueryParam);
                $this->setFilters($filters);
            }

            // Options
            $queryParam = (
                $prefix === '' ?
                '$options' :
                '$' . $prefix . 'Options'
            );
            $optionsQueryParam = $queryParams->getString($queryParam, null);
            if ($optionsQueryParam !== null) {
                $options = new OptionsQueryParam($optionsQueryParam);
                $this->setOptions($options);
            }

            // Order bys
            $queryParam = (
                $prefix === '' ?
                '$orderBy' :
                '$' . $prefix . 'OrderBy'
            );
            $orderByQueryParam = $queryParams->getString($queryParam, null);
            if ($orderByQueryParam !== null) {
                $orderBy = new OrderByQueryParam($orderByQueryParam);
                $this->setOrderBy($orderBy);
            }
        }
    }

    public function setQueryParams(QueryParams $queryParams): static
    {
        $this->setFilters($queryParams->getFilters());
        $this->setOptions($queryParams->getOptions());
        $this->setOrderBy($queryParams->getOrderBy());
        $this->setQueryMode($queryParams->getQueryMode());

        return $this;
    }

    public function getQueryMode(): ?string
    {
        return $this->queryMode;
    }
    public function setQueryMode(?string $value): static
    {
        if (!$this->isValidQueryMode($value)) {
            throw new InvalidArgumentException('Query mode is invalid. (' . $value . ')');
        }

        if ($this->queryMode !== $value) {
            $this->queryMode = $value;
            $this->resetRequired = true;
        }

        return $this;
    }

    public function getFilters(): ?FiltersQueryParam
    {
        return $this->filters;
    }
    public function setFilters(?FiltersQueryParam $value): static
    {
        $this->filters = $value;
        return $this;
    }
    public function addFilters(FiltersQueryParam $value): static
    {
        $filters = $this->getFilters();

        if ($filters === null) {
            $this->setFilters($value);
            return $this;
        }

        $filters->addQueryParamString($value->getQueryParamString());

        return $this;
    }

    public function getOptions(): ?OptionsQueryParam
    {
        return $this->options;
    }
    public function setOptions(?OptionsQueryParam $value): static
    {
        $this->options = $value;
        return $this;
    }
    public function addOptions(OptionsQueryParam $value): static
    {
        $options = $this->getOptions();

        if ($options === null) {
            $this->setOptions($value);
            return $this;
        }

        $options->addQueryParamString($value->getQueryParamString());

        return $this;
    }

    public function getOrderBy(): ?OrderByQueryParam
    {
        return $this->orderBy;
    }
    public function setOrderBy(?OrderByQueryParam $value): static
    {
        $this->orderBy = $value;
        return $this;
    }
    public function addOrderBy(OrderByQueryParam $value): static
    {
        $orderBy = $this->getOrderBy();

        if ($orderBy === null) {
            $this->setOrderBy($value);
            return $this;
        }

        $orderBy->addQueryParamString($value->getQueryParamString());

        return $this;
    }

    protected function isValidQueryMode(?string $mode): bool
    {
        if ($mode === null) {
            return true;
        }

        return false;
    }

    protected function isValidFilter(
        string $left,
        mixed $right,
        string $operator
    ): bool
    {
        return ($left === 'id' && is_int($right));
    }

    protected function isValidOption(string $option): bool
    {
        return false;
    }

    protected function isValidOrderBy(string $key, string $direction): bool
    {
        return ($key === 'id');
    }

    public function overrideQuery(
        SelectQueryInterface $query
    ): SelectQueryInterface
    {
        $query->columns('*');
        $query = $this->applyFilters($query);
        $query = $this->applyOptions($query);
        $query = $this->applyOrderBy($query);
        $this->resetRequired = false;

        return $query;
    }

    private function applyFilters(
        SelectQueryInterface $query
    ): SelectQueryInterface
    {
        if (!$this->getFilters()) {
            return $query;
        }

        if ($this->resetRequired) {
            $this->getFilters()->clean(function(...$values) {
                return $this->isValidFilter(...$values);
            }, true);
        }

        $parts = $this->getFilters()->getParts();

        if (!$parts) {
            return $query;
        }

        $where = $query->getWhere();

        foreach ($parts as $part) {
            if ($part[0] === '(') {
                if ($part[1] === 'OR') {
                    $where->orOpen();
                } else {
                    $where->andOpen();
                }
                continue;
            }

            if ($part[0] === ')') {
                if ($part[1] === 'OR') {
                    $where->orClose();
                } else {
                    $where->andClose();
                }
                continue;
            }

            $query = $this->applyFilter($query, $part[0], $part[1], $part[2]);
        }

        return $query;
    }

    protected function applyFilter(
        SelectQueryInterface $query,
        string $left,
        mixed $right,
        string $operator
    ): SelectQueryInterface
    {
        $where = $query->getWhere();
        $where->compare($left, $right, $operator);

        return $query;
    }

    private function applyOptions(
        SelectQueryInterface $query
    ): SelectQueryInterface
    {
        if (!$this->getOptions()) {
            return $query;
        }

        if ($this->resetRequired) {
            $this->getOptions()->clean(function(...$values) {
                return $this->isValidOption(...$values);
            }, true);
        }

        $parts = $this->getOptions()->getParts();

        foreach ($parts as $part) {
            $this->applyOption($query, $part);
        }

        return $query;
    }

    protected function applyOption(
        SelectQueryInterface $query,
        string $option
    ): SelectQueryInterface
    {
        return $query;
    }

    private function applyOrderBy(SelectQueryInterface $query): SelectQueryInterface
    {
        if (!$this->getOrderBy()) {
            return $query;
        }

        if ($this->resetRequired) {
            $this->getOrderBy()->clean(function(...$values) {
                return $this->isValidOrderBy(...$values);
            }, true);
        }

        $parts = $this->getOrderBy()->getParts();

        if (!$parts) {
            return $query;
        }

        $columns = [];

        foreach ($parts as $part) {
            $orderBy = $this->getOrderByColumn($query, $part[0], $part[1]);

            $orderBy = array_values($orderBy);

            if (is_array($orderBy[0])) {
                $columns = array_merge($columns, $orderBy);
            } else {
                $columns[] = $orderBy;
            }
        }

        $query->orderBy(...$columns);

        return $query;
    }

    protected function getOrderByColumn(
        SelectQueryInterface $query,
        string $key,
        string $direction
    ): array
    {
        return [$query->getTable(), $key, $direction];
    }
}
