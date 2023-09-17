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
        $errors = $this->getErrors($data);
        $errors = [...$errors];

        // Only clean data that doesn't have errors.
        $diff = array_diff_key($data, $errors);
        if ($diff) {
            $diff = $this->clean($diff);
            $data = [...$data, ...$diff];
        }

        return [$data, $errors];
    }
}
