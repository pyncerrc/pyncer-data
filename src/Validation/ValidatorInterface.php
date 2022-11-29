<?php
namespace Pyncer\Data\Validation;

interface ValidatorInterface
{
    public function validateData(array $data): array;
}
