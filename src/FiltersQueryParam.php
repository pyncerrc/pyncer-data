<?php
namespace Pyncer\Data;

use Pyncer\Data\AbstractQueryParam;
use Pyncer\Exception\InvalidArgumentException;

use function array_merge;
use function array_pop;
use function call_user_func_array;
use function count;
use function explode;
use function filter_var;
use function floatval;
use function implode;
use function intval;
use function is_array;
use function md5;
use function Pyncer\Array\unset_empty as pyncer_array_unset_empty;
use function strlen;
use function strpos;
use function strval;
use function substr;
use function trim;

class FiltersQueryParam extends AbstractQueryParam
{
    private array $stringMap;
    private array $bracketMap;
    private ?array $cleanedParts = null;

    public function getParts(): array
    {
        return $this->cleanedParts ?? parent::getParts();
    }
    public function hasFilter(string $name): bool
    {
        $filter = $this->getFilter($name);
        return ($filter !== null);
    }
    public function getFilter(string $name): ?string
    {
        $depth = 0;

        foreach ($this->getParts() as $part) {
            if ($part[0] === '(') {
                ++$depth;
                continue;
            }

            if ($part[0] === ')') {
                --$depth;
                continue;
            }

            if ($depth > 1) {
                continue;
            }

            if ($part[0] === $name) {
                return $part;
            }
        }

        return null;
    }
    public function hasFilterValue(string $name): bool
    {
        $filter = $this->getFilter($name);

        if ($filter === null) {
            return false;
        }

        if ($filter[2] === '=') {
            return true;
        }

        return false;
    }
    public function getFilterValue(string $name): null|bool|int|float|string
    {
        $filter = $this->getFilter($name);

        if ($filter === null) {
            return null;
        }

        if ($filter[2] === '=') {
            return $filter[1];
        }

        return null;
    }

    protected function parseQueryParam(string $queryParamString): array
    {
        $this->stringMap = [];
        $this->bracketMap = [];

        if ($queryParamString === '') {
            return [];
        }

        $queryParamString = $this->parseFilterStrings($queryParamString);

        //print_r($queryParamString); echo "\n";

        $parts = $this->parseFilterParts($queryParamString);

        //print_r($parts); echo "\n";

        $this->stringMap = [];
        $this->bracketMap = [];

        return $parts;
    }

    public function clean(callable $validate, bool $reset = false): void
    {
        if ($reset) {
            $this->cleanedParts = null;
        }

        $newParts = [];

        foreach ($this->getParts() as $value) {
            if ($value[0] === '(' || $value[0] === ')') {
                $newParts[] = $value;
                continue;
            }

            if (!call_user_func_array($validate, $value)) {
                continue;
            }

            $newParts[] = $value;
        }

        // Remove instances of brackets with no children
        $this->cleanedParts = [];

        $lastKey = -1;
        foreach ($newParts as $key => $value) {
            if ($value[0] === ')') {
                if ($this->cleanedParts[$lastKey][0] === '(') {
                    array_pop($this->cleanedParts);
                    --$lastKey;
                } else {
                    $this->cleanedParts[] = $value;
                }

                continue;
            }

            $this->cleanedParts[] = $value;
            ++$lastKey;
        }
    }

    /**
    * Parse out strings and replace with hash to make parsing parts easier.
    */
    private function parseFilterStrings(string $queryParamString): string
    {
        $newQueryParamString = '';
        $pos = 0;
        $len = strlen($queryParamString);
        $prev = '';
        $inString = false;

        for ($i = 0; $i < $len; ++$i) {
            $char = substr($queryParamString, $i, 1);

            if ($char === '\'' && $prev !== '\\') {
                if ($inString) {
                    $inString = false;

                    $string = substr($queryParamString, $pos, $i - $pos);
                    $md5 = md5($string);

                    $this->stringMap[$md5] = $string;
                    $newQueryParamString .= $md5;

                    $pos = $i;
                } else {
                    $inString = true;

                    $newQueryParamString .= substr($queryParamString, $pos, $i - $pos + 1);
                    $pos = ($i + 1);
                }
            }

            if ($prev === '\\') {
                $prev = '';
            } else {
                $prev = $char;
            }
        }

        $newQueryParamString .= substr($queryParamString, $pos);

        if ($inString) {
            throw new InvalidArgumentException('Invalid filter value.');
        }

        return $newQueryParamString;
    }
    private function parseFilterParts(string $queryParamString): array
    {
        if (strpos($queryParamString, '(') === false) {
            return $this->parseFilterConditionString($queryParamString);
        }

        $bracketGroups = $this->parseFilterBracketGroups($queryParamString);

        if (!is_array($bracketGroups)) {
            return $this->parseFilterConditionString($bracketGroups);
        }

        $conditions = [];
        $previousConditional = true;
        foreach ($bracketGroups as $key => $value) {
            if ($value === 'and' || $value === 'or') {
                if ($previousConditional) {
                    throw new InvalidArgumentException('Invalid filter value.');
                }

                $conditions[] = $value;
                $previousConditional = true;
                continue;
            }

            if (!$previousConditional) {
                if (substr($value, 0, 3) == 'or ') {
                    $conditions[] = 'or';
                    $previousConditional = true;

                    $value = substr($value, 3);
                } elseif (substr($value, 0, 4) == 'and ') {
                    $conditions[] = 'and';
                    $previousConditional = true;

                    $value = substr($value, 4);
                } else {
                    throw new InvalidArgumentException('Invalid filter value.');
                }
            }

            $condition = false;
            if (substr($value, -3) == ' or') {
                $condition = 'or';
                $value = substr($value, 0, -3);
            } elseif (substr($value, -4) == ' and') {
                $condition = 'and';
                $value = substr($value, 0, -4);
            }

            // Bracket group, so parse it!
            if (substr($value, 0, 1) == '(') {
                $value = substr($value, 1, -1);
                $md5 = md5($value);
                $this->bracketMap[$md5] = $this->parseFilterParts($value);
                $value = $md5;
            }

            $conditions[] = $value;

            if ($condition) {
                $conditions[] = $condition;
                $previousConditional = true;
            } else {
                $previousConditional = false;
            }
        }

        return $this->parseFilterConditionString(implode(' ', $conditions));
    }
    private function parseFilterBracketGroups(string $queryParamString): array
    {
        $parts = [];
        $pos = 0;
        $len = strlen($queryParamString);
        $bracketDepth = 0;

        for ($i = 0; $i < $len; ++$i) {
            $char = substr($queryParamString, $i, 1);

            if ($char === '(') {
                if (!$bracketDepth) {
                    if ($i !== 0) {
                        $parts[] = trim(substr($queryParamString, $pos, $i - $pos));
                    }

                    $pos = $i + 1;
                }

                ++$bracketDepth;
            } elseif ($char === ')') {
                --$bracketDepth;

                if ($bracketDepth < 0) {
                    throw new InvalidArgumentException('Invalid filter value.');
                } elseif (!$bracketDepth) {
                    $parts[] = '(' . trim(substr($queryParamString, $pos, $i - $pos)) . ')';

                    $pos = $i + 1;
                }
            }
        }

        $last = trim(substr($queryParamString, $pos));

        if ($last !== '') {
            $parts[] = $last;
        }

        if ($bracketDepth) {
            throw new InvalidArgumentException('Invalid filter value.');
        }

        if (count($parts) === 1) {
            if (strpos($parts[0], '(') === false) {
                return $parts[0];
            }

            return $this->parseFilterBracketGroups($parts[0]);
        }

        return $parts;

        $newParts = [];
        foreach ($parts as $key => $value) {
            if (substr($value, 0, 4) == 'and ') {
                $value = explode('or', $value, 2);
                $newParts[] = trim($value[0]);
                if (isset($value[1])) {
                    $newParts[] = 'or ' . trim($value[1]);
                }
            } else {
                $newParts[] = $value;
            }
        }

        return $newParts;
    }
    private function parseFilterConditionString(string $queryParamString): array
    {
        $conditions = [];

        $ors = explode(' or ', $queryParamString);

        if (count($ors) > 1) {
            $conditions[] = ['(', 'OR'];
        }

        foreach ($ors as $or) {
            $ands = explode(' and ', $or);

            if (count($ands) > 1) {
                $conditions[] = ['(', 'AND'];
            }

            foreach ($ands as $and) {
                $and = explode(' ', $and);
                $and = pyncer_array_unset_empty($and);

                // Child (bracket) map insert
                if (count($and) == 1) {
                    $conditions = array_merge($conditions, $this->bracketMap[$and[0]]);
                    continue;
                }

                if ($and[0] === 'not') {
                    $operator = $this->convertFilterOperator($and[2], true);

                    $condition = [$and[1], $and[3], $operator];
                } else {
                    $operator = $this->convertFilterOperator($and[1]);

                    $condition = [$and[0], $and[2], $operator];
                }

                // In theory you could have a field with quotes, but more likely its an error,
                // so we will treat it as such
                if ($this->isStringValue($condition[0])) {
                    throw new InvalidArgumentException('Invalid filter value.');
                    //$condition[0] = '\'' . $this->stringMap[substr($condition[0], 1, -1)] . '\'';
                }

                if ($this->isStringValue($condition[1])) {
                    $condition[1] = $this->stringMap[substr($condition[1], 1, -1)];
                } else {
                    $condition[1] = $this->convertFilterValue($condition[1]);
                }

                $conditions[] = $condition;
            }

            if (count($ands) > 1) {
                $conditions[] = [')', 'AND'];
            }
        }

        if (count($ors) > 1) {
            $conditions[] = [')', 'OR'];
        }

        return $conditions;
    }

    private function convertFilterOperator(string $operator, bool $not = false): string
    {
        if ($not) {
            switch ($operator) {
                case 'gt':
                    $operator = '<=';
                    break;
                case 'ge':
                    $operator = '<';
                    break;
                case 'lt':
                    $operator = '>=';
                    break;
                case 'le':
                    $operator = '>';
                    break;
                case 'eq':
                    $operator = '!=';
                    break;
                case 'ne':
                    $operator = '=';
                    break;
                default:
                    throw new InvalidArgumentException('Invalid filter value.');
            }
        } else {
            switch ($operator) {
                case 'gt':
                    $operator = '>';
                    break;
                case 'ge':
                    $operator = '>=';
                    break;
                case 'lt':
                    $operator = '<';
                    break;
                case 'le':
                    $operator = '<=';
                    break;
                case 'eq':
                    $operator = '=';
                    break;
                case 'ne':
                    $operator = '!=';
                    break;
                default:
                    throw new InvalidArgumentException('Invalid filter value.');
            }
        }

        return $operator;
    }
    private function convertFilterValue(string $value): mixed
    {
        if ($value === 'null') {
            return null;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($this->isIntegerValue($value)) {
            return intval($value);
        }

        if ($this->isFloatValue($value)) {
            return floatval($value);
        }

        if ($this->isStringValue($value)) {
            return substr($value, 1, -1);
        }

        return strval($value);
    }

    private function isStringValue(string $value): bool
    {
        if (substr($value, 0, 1) === '\'' &&
            substr($value, -1, 1) === '\'' &&
            strlen($value) > 1
        ) {
            return true;
        }

        return false;
    }
    private function isIntegerValue(string $value): bool
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false);
    }
    private function isFloatValue(string $value): bool
    {
        return (filter_var($value, FILTER_VALIDATE_FLOAT) !== false);
    }
}
