<?php
namespace Pyncer\Data\Mapper\Query;

use Pyncer\Data\Model\ModelInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\Record\SelectQueryInterface;

interface MapperQueryInterface
{
    public function overrideModel(
        ConnectionInterface $connection,
        ModelInterface $model,
        array $data
    ): ModelInterface;

    public function overrideQuery(
        SelectQueryInterface $query
    ): SelectQueryInterface;
}
