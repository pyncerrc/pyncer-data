<?php
namespace Pyncer\Data\MapperQuery;

interface QueryParamInterface
{
    public function getQueryParamString(): string;
    public function setQueryParamString(string $value): static;
    public function addQueryParam(QueryParamInterface $value): static;
    public function addQueryParamString(string $value): static;
    public function getCleanQueryParamString(): string;
    public function getParts(): array;
    public function clean(callable $validate, bool $reset = false): void;
    public function hasCleaned(): bool;
}
