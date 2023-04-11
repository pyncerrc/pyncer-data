<?php
namespace Pyncer\Data\Formatting;

interface FormatterInterface
{
    /**
     * @return array<string, mixed>
     */
    public function formatData(iterable $data): array;

    /**
     * @return array<string, mixed>
     */
    public function unformatData(iterable $data): array;
}
