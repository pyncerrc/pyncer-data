<?php
namespace Pyncer\Data\Formatting;

use Pyncer\Data\Formatting\FormatterInterface;
use Traversable;

use function iterator_to_array;

class VoidFormatter implements FormatterInterface
{
    public function formatData(iterable $data): array
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data, true);
        }

        return $data;
    }
    public function unformatData(iterable $data): array
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data, true);
        }

        return $data;
    }
}
