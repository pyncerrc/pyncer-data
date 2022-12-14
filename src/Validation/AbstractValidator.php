<?php
namespace Pyncer\Data\Validation;

use Pyncer\Data\Validation\ValidatorInterface;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Database\ConnectionTrait;
use Pyncer\Validation\DataValidator;

abstract class AbstractValidator extends DataValidator implements
    ValidatorInterface
{
    use ConnectionTrait;

    public function __construct(ConnectionInterface $connection)
    {
        $this->setConnection($connection);

        parent::__construct();
    }

    public function validateData(array $data): array
    {
        $data = $this->clean($data);
        $errors = $this->getErrors($data);

        return [$data, $errors];
    }
}
