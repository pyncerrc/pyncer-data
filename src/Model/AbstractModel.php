<?php
namespace Pyncer\Data\Model;

use ArgumentCountError;
use BadMethodCallException;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\DataMap;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\OutOfBoundsException;
use Pyncer\Exception\LogicException;
use Pyncer\Iterable\Map;
use Pyncer\Iterable\MapInterface;
use Stringable;

use function array_key_exists;
use function array_map;
use function array_merge;
use function call_user_func;
use function explode;
use function implode;
use function is_iterable;
use function method_exists;
use function Pyncer\Utility\to_snake_case as pyncer_to_snake_case;

abstract class AbstractModel extends Map implements ModelInterface
{
    protected DataMap $sideModels;
    protected DataMap $extraData;
    protected bool $isDefault;

    private static bool $constructIsDefault = false;

    public function __construct(iterable $values = [])
    {
        $this->isDefault = self::$constructIsDefault;
        $this->sideModels = new DataMap();
        $this->extraData = new DataMap();

        parent::__construct($values);
    }

    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        if (!$this->isDefault) {
            return self::getDefaultModel()->get($key);
        }

        throw new OutOfBoundsException('Invalid key specified. (' . $key . ')');
    }

    public function set(string $key, mixed $value): static
    {
        if (!is_null($value) && !is_scalar($value) && !is_array($value)) {
            throw new LogicException(
                'Internal data array can only have scalar, array, and null values.'
            );
        }

        $this->values[$key] = $value;
        return $this;
    }

    public function has(string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->values)) {
                if ($this->isDefault) {
                    return false;
                }

                if (!self::getDefaultModel()->has($key)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function delete(string ...$keys): static
    {
        foreach ($keys as $key) {
            if (self::getDefaultModel()->has($key)) {
                if (!$this->isDefault) {
                    $this->values[$key] = self::getDefaultModel()->get($key);
                }
            } else {
                unset($this->values[$key]);
            }
        }

        return $this;
    }

    public function getId(): int
    {
        /** @var int **/
        return $this->get('id');
    }
    public function setId(int $value): static
    {
        $this->set('id', $value);
        return $this;
    }

    public function equals(mixed $other): bool
    {
        if (!$other instanceof static) {
            return false;
        }

        return ($this->getId() === $other->getId());
    }

    protected function nullify(mixed $value): mixed
    {
        if ($value === '' || $value === 0 || $value === 0.0 || $value === []) {
            $value = null;
        }

        return $value;
    }

    public function getData(): array
    {
        if ($this->isDefault) {
            return parent::getData();
        }

        $data = [];

        foreach (self::getDefaultModel()->getKeys() as $key) {
            $value = $this->callGet($key);

            if (is_null($value) || is_scalar($value) || is_array($value)) {
                $data[$key] = $value;
            } else {
                $data[$key] = $this->get($key);
            }
        }

        return $data;
    }

    public function setData(iterable ...$values): static
    {
        if (!$this->isDefault) {
            $this->values = static::getDefaultData();
        }

        $this->addData(...$values);

        return $this;
    }

    public function addData(iterable ...$values): static
    {
        if ($this->isDefault) {
            parent::addData(...$values);
            return $this;
        }

        $defaultModel = self::getDefaultModel();

        foreach ($values as $iterableValues) {
            foreach ($iterableValues as $key => $value) {
                if ($defaultModel->has($key)) {
                    $this->callSet($key, $value);
                }
            }
        }

        return $this;
    }

    public function getAllData(): array
    {
        $data = $this->getData();

        // Do not allow side models to override default data
        $sideModelData = $this->getSideModels()->getData();
        foreach ($sideModelData as $key => $value) {
            if (!$this->has($key)) {
                $data[$key] = $value;
            }
        }

        // Do not allow extra data to override default data
        $extraData = $this->getExtraData();
        foreach ($extraData as $key => $value) {
            if (!$this->has($key)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public static function getDefaultData(): array
    {
        return [
            'id' => 0,
        ];
    }

    final public static function getDefaultModel(): ModelInterface
    {
        static $default = [];

        if (!array_key_exists(static::class, $default)) {
            $default[static::class] = self::forgeDefaultModel();
        }

        return $default[static::class];
    }

    final protected static function forgeDefaultModel(): ModelInterface
    {
        $class = static::class;

        self::$constructIsDefault = true;
        $model = new $class(static::getDefaultData());
        self::$constructIsDefault = false;

        return $model;
    }

    public function getSideModels(): MapInterface
    {
        return $this->sideModels;
    }

    public function getSideModel(string $name): mixed
    {
        return $this->getSideModels()->get($name);
    }
    public function setSideModel(string $name, mixed $value): mixed
    {
        return $this->getSideModels()->set($name, $value);
    }

    public function hasSideModel(string $key): bool
    {
        return $this->getSideModels()->has($key);
    }

    public function hasSideModels(string ...$keys): bool
    {
        return $this->getSideModels()->has(...$keys);
    }

    public function getExtraData(): array
    {
        return $this->extraData->getData();
    }
    public function setExtraData(iterable ...$values): static
    {
        $this->extraData->setData(...$values);
        return $this;
    }
    public function addExtraData(iterable ...$values): static
    {
        $this->extraData->addData(...$values);
        return $this;
    }

    public function &offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException('Offset value must be a string.');
        }

        return $this->callGet($offset);
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new InvalidArgumentException('Offset value must be a string.');
        }

        $this->callSet($offset, $value);
    }

    private function callSet(string $key, mixed $value): void
    {
        if ($this->ignoreCallKey($key)) {
            $this->set($key, $value);
            return;
        }

        $func = explode('_', $key);
        $func = array_map('ucfirst', $func);
        $func = 'set' . implode('', $func);

        if (method_exists($this, $func)) {
            call_user_func($this->$func(...), $value);
        } else {
            $this->set($key, $value);
        }
    }
    private function callGet(string $key): mixed
    {
        if ($this->ignoreCallKey($key)) {
            return $this->get($key);
        }

        $func = explode('_', $key);
        $func = array_map('ucfirst', $func);
        $func = 'get' . implode('', $func);

        if (method_exists($this, $func)) {
            return call_user_func($this->$func(...));
        }

        return $this->get($key);
    }

    protected function ignoreCallKey(string $key): bool
    {
        $key = str_replace('_', '', strtolower($key));

        // Ignore keys that match existing built in get functions
        switch ($key) {
            case 'alldata':
            case 'allsidemodeldata':
            case 'data':
            case 'defaultdata':
            case 'defaultmodel':
            case 'extradata':
            case 'sidemodel':
            case 'sidemodels':
                return true;
        }

        return false;
    }

    public function __call (string $name, array $arguments): mixed
    {
        if (str_contains($name, '_')) {
            throw new BadMethodCallException(
                'Call to undefined method ' .
                static::class . '::' . $name
            );
        }

        $key = pyncer_to_snake_case($name);

        $isSetter = false;

        if (substr($key, 0, 4) === 'set_') {
            $isSetter = true;
        } elseif (substr($key, 0, 4) !== 'get_') {
            throw new BadMethodCallException(
                'Call to undefined method ' .
                static::class . '::' . $name
            );
        }

        $key = substr($key, 4);

        if (!$this->has($key)) {
            throw new BadMethodCallException(
                'Call to undefined method ' .
                static::class . '::' . $name
            );
        }

        if ($isSetter) {
            if (count($arguments) !== 1) {
                throw new ArgumentCountError(
                    static::class . '::' . $name .
                    ' expects exactly 1 argument, ' .
                    count($arguments) . ' given'
                );
            }

            $this->set($key, $arguments[0]);
            return $this;
        }

        if (count($arguments) !== 0) {
            throw new ArgumentCountError(
                static::class . '::' . $name .
                ' expects exactly 0 argument, ' .
                count($arguments) . ' given'
            );
        }

        return $this->get($key);
    }
}
