<?php
namespace Pyncer\Data\Mapper\Query;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Data\FiltersQueryParam;
use Pyncer\Data\Mapper\Query\AbstractMapperQuery;
use Pyncer\Data\Mapper\Query\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\OptionsQueryParam;
use Pyncer\Data\OrderByQueryParam;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Query\SelectQueryInterface;
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
    private bool $resetRequired = false;

    private ?FiltersQueryParam $filter = null;
    private ?OptionsQueryParam $options = null;
    private ?OrderByQueryParam $orderBy = null;

    public function __construct(
        ?PsrServerRequestInterface $request = null,
        string $prefix = ''
    )
    {
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
            $filtersQueryParam = $queryParams->getStr($queryParam, null);
            if ($filtersQueryParam !== null) {
                $filter = new FiltersQueryParam($filtersQueryParam);
                $this->setFilter($filter);
            }

            // Options
            $queryParam = (
                $prefix === '' ?
                '$options' :
                '$' . $prefix . 'Options'
            );
            $optionsQueryParam = $queryParams->getStr($queryParam, null);
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
            $orderByQueryParam = $queryParams->getStr($queryParam, null);
            if ($orderByQueryParam !== null) {
                $orderBy = new OrderByQueryParam($orderByQueryParam);
                $this->setOrderBy($orderBy);
            }
        }
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

    public function getFilter(): ?FiltersQueryParam
    {
        return $this->filter;
    }
    public function setFilter(?FiltersQueryParam $value): static
    {
        $this->filter = $value;
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

    public function getOrderBy(): ?OrderByQueryParam
    {
        return $this->orderBy;
    }
    public function setOrderBy(?OrderByQueryParam $value): static
    {
        $this->orderBy = $value;
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
        return false;
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
        // TODO: Allow overriding this via function that defaults to none
        // $query->groupBy('id');
        $query = $this->applyOrderBy($query);
        $this->resetRequired = false;

        return $query;
    }

    private function applyFilters(
        SelectQueryInterface $query
    ): SelectQueryInterface
    {
        if (!$this->getFilter()) {
            return $query;
        }

        if ($this->resetRequired) {
            $this->getFilter()->clean(function(...$values) {
                return $this->isValidFilter(...$values);
            }, true);
        }

        $parts = $this->getFilter()->getParts();

        if (!$parts) {
            return $query;
        }

        $where = $query->getWhere();

        foreach ($parts as $part) {
            if ($part[0] === '(') {
                if ($part[1] === 'OR') {
                    $where->orBlockOpen();
                } else {
                    $where->andBlockOpen();
                }
                continue;
            }

            if ($part[0] === ')') {
                if ($part[1] === 'OR') {
                    $where->orBlockClose();
                } else {
                    $where->andBlockClose();
                }
                continue;
            }

            $query = $this->applyFilter($query, $part[0], $part[1], $part[2]);
        }

        return $query;
    }

    protected function applyFilter(
        SelectQueryInterface $query,
        mixed $left,
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
        $key,
        $direction
    ): array
    {
        return [$query->getTable(), $key, $direction];
    }
}

/*
(name eq 'Milk' or name eq 'Eggs') and ((price lt 2.55 and price lt 2.55) or price eq 2)

(name eq 'Milk' or name eq 'Eggs'
and ((price lt 2.55 and price lt 2.55
or price eq 2

(name eq 'Milk' or name eq 'Eggs') and ((price lt 2.55 and price lt 2.55) or price eq 2)
(name eq 'Milk' or name eq 'Eggs') and (price lt 2.55 and (price lt 2.55 or price eq 2))

--
(name eq 'Milk' or name eq 'Eggs') and ((price lt 2.55 and price lt 2.55) or price eq 2)

(name eq 'Milk' or name eq 'Eggs') and ((price lt 2.55 and price lt 2.55) or price eq 2)

((name eq 'Milk') or (name eq 'Eggs') and ((price lt 2.55 and price lt 2.55)) or (price eq 2))

---

((name eq 'Milk') or (name eq 'Eggs') and (price lt 2.55 and price lt 2.55) or (price eq 2))

--
name eq 'Milk' or name eq 'Eggs' and price lt 2.55 and price lt 2.55 or price eq 2

name eq 'Milk' or (name eq 'Eggs' and price lt 2.55 and price lt 2.55) or price eq 2

(name eq 'Milk') or (name eq 'Eggs' and price lt 2.55 and price lt 2.55) or (price eq 2)

((name eq 'Milk') or
(name eq 'Eggs') and (price lt 2.55 and (price lt 2.55) or
(price eq 2)))

1. Parse out strings, replace with md5
2. Replace '(' and ')' with ' ( ' and ' ) '
3. Explode ' or ', implode ' ) or ( ' and surround with '( ' and ' )'
4. Iterate over each character to group brackets into array ['(...)', 'and', '((...) and (...))', 'or', '(...)']
5. create bracket group that is compatible with where pdb where conditions

[
    '(OR',
        'name = milk',
        'name = eggs'
    ')'
    '(OR',
        '(AND',
            price lt 2.55
            price lt 2.55
        ')',
        price eq 2
    ')'
]

(name eq 'Milk' or name eq 'Eggs') and price lt 2.55 or price eq 2

'conditions' => [
    '(',
    ['not' => false, 'key' => 'name', 'operator' => '=', 'value' 'Milk'],
    'or',
    ['not' => false, 'key' => 'name', 'operator' => '=', 'value' 'Eggs'],
    ')',
    ['not' => false, 'key' => 'price', 'operator' => '<', 'value' 2.55]
    'or',
    ['not' => false, 'key' => 'price', 'operator' => '=', 'value' 2 ]
]
*/
