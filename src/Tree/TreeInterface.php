<?php
namespace Pyncer\Data\Tree;

use Pyncer\Data\Model\ModelInterface;

interface TreeInterface
{
    public function getParents(?int $id, ?int $parentId = null): iterable;
    public function getChildren(?int $id): iterable;
    public function getDescendents(?int $id): iterable;
    public function hasItem(int $id): bool;
    public function getItem(int $id): ModelInterface;
}
