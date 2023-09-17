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
}
