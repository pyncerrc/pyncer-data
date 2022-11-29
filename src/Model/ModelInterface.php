<?php
namespace Pyncer\Data\Model;

use Pyncer\Data\Model\ModelInterface;
use Pyncer\Iterable\MapInterface;
use Pyncer\Utility\EqualsInterface;

interface ModelInterface extends MapInterface, EqualsInterface
{
    public function getId(): int;
    public function setId(int $value): static;

    public function getData(): array;
    public function getAllData(): array;
    public static function getDefaultData(): array;

    public function getSideModels(): MapInterface;
    public function getSideModel(string $name): ModelInterface;
    public function hasSideModels(string ...$keys): bool;
}
