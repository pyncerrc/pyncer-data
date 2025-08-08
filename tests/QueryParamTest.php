<?php
namespace Pyncer\Tests\Data;

use PHPUnit\Framework\TestCase;

class QueryParamTest extends TestCase
{
    public function testFiltersQueryParam(): void
    {
        $filters = "a eq 'b' and c eq 'd'";
        $filters = new \Pyncer\Data\MapperQuery\FiltersQueryParam($filters);

        $this->assertEquals(
            $filters->getParts(),
            [
                ['(', 'AND'],
                ['a', 'b', '='],
                ['c', 'd', '='],
                [')', 'AND'],
            ]
        );

        $this->assertEquals(
            $filters->getCleanQueryParamString(),
            "( a eq 'b' and c eq 'd' )"
        );

        $filters = "a eq 'b' and (c eq 'd' or e gt 3)";
        $filters = new \Pyncer\Data\MapperQuery\FiltersQueryParam($filters);

        $this->assertEquals(
            $filters->getParts(),
            [
                ['(', 'AND'],
                ['a', 'b', '='],
                ['(', 'OR'],
                ['c', 'd', '='],
                ['e', 3, '>'],
                [')', 'OR'],
                [')', 'AND'],
            ]
        );

        $this->assertEquals(
            $filters->getCleanQueryParamString(),
            "( a eq 'b' and ( c eq 'd' or e gt 3 ) )"
        );

        $filters->clean(function($value) {
            if ($value[0] === 'e') {
                return false;
            }

            return true;
        });

        $this->assertEquals(
            $filters->getParts(),
            [
                ['(', 'AND'],
                ['a', 'b', '='],
                ['c', 'd', '='],
                [')', 'AND'],
            ]
        );

        $this->assertEquals(
            $filters->getCleanQueryParamString(),
            "( a eq 'b' and c eq 'd' )"
        );
    }

    public function testOptionsQueryParam(): void
    {
        $options = 'include-test';
        $options = new \Pyncer\Data\MapperQuery\OptionsQueryParam($options);

        $this->assertEquals(
            $options->getParts(),
            [
                'include-test',
            ]
        );

        $this->assertEquals(
            $options->getCleanQueryParamString(),
            'include-test'
        );

        $options = 'include-test,include-foo,include-bar';
        $options = new \Pyncer\Data\MapperQuery\OptionsQueryParam($options);

        $options->clean(function($value) {
            if ($value === 'include-foo') {
                return false;
            }

            return true;
        });

        $this->assertEquals(
            $options->getParts(),
            [
                'include-test',
                'include-bar',
            ]
        );

        $this->assertEquals(
            $options->getCleanQueryParamString(),
            'include-test,include-bar',
        );
    }

    public function testOrderByQueryParam(): void
    {
        $orderBy = 'a desc';
        $orderBy = new \Pyncer\Data\MapperQuery\OrderByQueryParam($orderBy);

        $this->assertEquals(
            $orderBy->getParts(),
            [
                ['a', '<'],
            ]
        );

        $this->assertEquals(
            $orderBy->getCleanQueryParamString(),
            "a desc"
        );

        $orderBy = 'a desc, b asc, c desc';
        $orderBy = new \Pyncer\Data\MapperQuery\OrderByQueryParam($orderBy);

        $orderBy->clean(function($value) {
            if ($value[0] === 'b') {
                return false;
            }

            return true;
        });

        $this->assertEquals(
            $orderBy->getParts(),
            [
                ['a', '<'],
                ['c', '<'],
            ]
        );

        $this->assertEquals(
            $orderBy->getCleanQueryParamString(),
            "a desc,c desc"
        );
    }
}
