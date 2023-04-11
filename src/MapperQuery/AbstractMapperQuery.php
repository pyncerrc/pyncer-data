<?php
namespace Pyncer\Data\MapperQuery;

use Pyncer\Data\MapperQuery\MapperQueryInterface;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\ConnectionTrait;
use Pyncer\Database\Record\SelectQueryInterface;

abstract class AbstractMapperQuery implements MapperQueryInterface
{
    use ConnectionTrait;

    public function __construct(ConnectionInterface $connection)
    {
        $this->setConnection($connection);
    }

    public function overrideModel(
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
