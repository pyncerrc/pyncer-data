<?php
namespace Pyncer\Data\Formatting;

use Pyncer\Data\Formatting\FormatterInterface;

class KeyFormatter implements FormatterInterface
{
    public function __construct(
        protected array $keys
    ) {}

    /**
     * @inheritDoc
     */
    public function formatData(iterable $data): array
    {
        $newData = [];

        foreach ($data as $key => $value) {
            $key = $this->keys[$key] ?? $key;

            $newData[$key] = $value;
        }

        return $newData;
    }

    /**
     * @inheritDoc
     */
    public function unformatData(iterable $data): array
    {
        $newData = [];

        $keys = array_flip($this->keys);

        foreach ($data as $key => $value) {
            $key = $keys[$key] ?? $key;

            $newData[$key] = $value;
        }

        return $newData;
    }
}
