<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;

abstract class AbstractMapperQuery implements MapperQueryInterface
{
    public function overrideModel(
        ConnectionInterface $connection,
        ModelInterface $model,
        array $data
    ): ModelInterface
    {
        return $model;
    }

    public function overrideQuery(
        SelectQueryInterface $query
    ): SelectQueryInterface
    {
        return $query;
    }
}
