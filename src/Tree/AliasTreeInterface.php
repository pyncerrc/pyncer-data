<?php
namespace Pyncer\Data\Tree;

use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Tree\TreeInterface;

interface AliasTreeInterface extends TreeInterface
{
    public function getAliases(?int $id, ?int $parentId = null): iterable;
    public function getAliasPath(?int $id, ?int $parentId = null): string;

    public function hasItemFromAlias(string $alias, ?int $parentId = null): bool;
    public function getItemFromAlias(string $alias, ?int $parentId = null): ModelInterface;
    public function hasItemFromAliasPath(string $path, ?int $parentId = null): bool;
    public function getItemFromAliasPath(string $path, ?int $parentId = null): ModelInterface;
}
