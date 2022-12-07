<?php
namespace Pyncer\Data\Formatting;

interface FormatterInterface
{
    public function formatData(iterable $data): array;
    public function unformatData(iterable $data): array;
}
