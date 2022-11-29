<?php
namespace Pyncer\Data\Validation;

use Pyncer\Data\Validation\ValidatorInterface
use Pyncer\Database\ConnectionInterface;
use Pyncer\Validation\DataValidator

abstract class AbstractValidator extends DataValidator implements
    ValidatorInterface
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        parent::__construct();
    }

    public function validateData(array $data): array
    {
        $data = $this->clean($data);
        $errors = $this->getErrors($data);

        return [$data, $errors];
    }
}
