<?php
namespace Pyncer\Data\Model;

use ArgumentCountError;
use BadMethodCallException;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Data\Model\SideModelMap;
use Pyncer\Exception\OutOfBoundsException;
use Pyncer\Exception\LogicException;
use Pyncer\Iterable\Map;
use Pyncer\Iterable\MapInterface;

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
    protected SideModelMap $sideModels;
    protected bool $isDefault;
    protected array $extraData;

    private static $constructIsDefault = false;

    public function __construct(iterable $values = [])
    {
        $this->isDefault = static::$constructIsDefault;
        $this->sideModels = new SideModelMap();
        $this->extraData = [];

        parent::__construct($values);
    }

    public function get(string $key): mixed
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }

        if (!$this->isDefault) {
            return static::getDefaultModel()->get($key);
        }

        throw new OutOfBoundsException('Invalid key specified. (' . $key . ')');
    }

    public function set(string $key, $value): static
    {
        if (!is_null($value) && !is_scalar($value)) {
            throw new LogicException(
                'Internal data array can only have scalar and null values.'
            );
        }

        $this->values[$key] = $value;
        return $this;
    }

    public function delete(string ...$keys): static
    {
        foreach ($keys as $key) {
            if (!$this->isDefault && static::getDefaultModel()->has($key)) {
                $this->values[$key] = static::getDefaultModel()->get($key);
            } else {
                unset($this->values[$key]);
            }
        }

        return $this;
    }

    public function getId(): int
    {
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
        if ($value === '' || $value === 0 || $value === 0.0) {
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

        foreach (static::getDefaultModel()->getKeys() as $key) {
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

        $defaultModel = static::getDefaultModel();

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

        $sideModelData = $this->getSideModels()->getData();

        if ($this->isDefault) {
            foreach ($sideModelData as $key => $value) {
                // Do not allow servants to override default data
                if (!$this->has($key)) {
                    $data[$key] = $value;
                }
            }

            foreach ($this->extraData as $key => $value) {
                if (!$this->has($key)) {
                    $data[$key] = $value;
                }
            }
        } else {
            foreach ($sideModelData as $key => $value) {
                // Do not allow servants to override default data
                if (!static::getDefaultModel()->has($key)) {
                    $data[$key] = $value;
                }
            }

            foreach ($this->extraData as $key => $value) {
                if (!static::getDefaultModel()->has($key)) {
                    $data[$key] = $value;
                }
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

        static::$constructIsDefault = true;
        $model = new $class(static::getDefaultData());
        static::$constructIsDefault = false;

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

    public function hasSideModels(string ...$keys): bool
    {
        return $this->getSideModels()->has(...$keys);
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }
    public function setExtraData(iterable ...$values): static
    {
        $this->extraData = [];
        return $this->addExtraData(...$values);
    }
    public function addExtraData(iterable ...$values): static
    {
        foreach ($values as $iterableValues) {
            foreach ($iterableValues as $key => $value) {
                $this->extraData[$key] = $value;
            }
        }

        return $this;
    }

    public function &offsetGet(mixed $offset): mixed
    {
        return $this->callGet($offset);
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
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
            call_user_func([$this, $func], $value);
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
            return call_user_func([$this, $func]);
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
