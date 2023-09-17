<?php
namespace Pyncer\Data\Formatting;

use Pyncer\Data\Formatting\FormatterInterface;
use Traversable;

class VoidFormatter implements FormatterInterface
{
    /**
     * @inheritDoc
     */
    public function formatData(iterable $data): array
    {
        /** @var array<string, mixed> **/
        return [...$data];
    }

    /**
     * @inheritDoc
     */
    public function unformatData(iterable $data): array
    {
        /** @var array<string, mixed> **/
        return [...$data];
    }
}
