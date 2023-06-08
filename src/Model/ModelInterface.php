<?php
namespace Pyncer\Data\Model;

use Pyncer\Iterable\MapInterface;
use Pyncer\Utility\EqualsInterface;

interface ModelInterface extends MapInterface, EqualsInterface
{
    public function getId(): int;
    public function setId(int $value): static;

    public function getAllData(): array;
    public static function getDefaultData(): array;

    public function getSideModels(): MapInterface;
    public function getSideModel(string $name): ModelInterface;
    public function hasSideModels(string ...$keys): bool;

    public function getExtraData(): array;
    public function setExtraData(iterable ...$values): static;
    public function addExtraData(iterable ...$values): static;
}
