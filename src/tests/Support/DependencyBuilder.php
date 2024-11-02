<?php

namespace Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * テスト用の集約、値オブジェクト等のインスタンスを生成する機能.
 *
 * テスト対象のクラスが依存する他のクラスのインスタンス生成を共通化する
 */
class DependencyBuilder
{
    /**
     * 共有インスタンス.
     */
    private static array $instances = [];

    /**
     * 生成対象の名前空間.
     */
    private string $classNamespace;

    /**
     * ファクトリの名前空間.
     */
    private string $factoryNamespace;

    /**
     * 生成済みのクラスファクトリ.
     */
    private Collection $factories;

    /**
     * コンストラクタ
     */
    public function __construct(string $classNamespace, string $factoryNamespace)
    {
        $this->classNamespace = \rtrim($classNamespace, '\\') . '\\';
        $this->factoryNamespace = \rtrim($factoryNamespace, '\\') . '\\';

        $this->factories = new Collection();
    }

    /**
     * 共有インスタンスを取得する.
     */
    public static function getInstance(string $classNamespace, string $factoryNamespace): self
    {
        $key = $classNamespace . ':' . $factoryNamespace;

        if (!\array_key_exists($key, static::$instances)) {
            static::$instances[$key] = new DependencyBuilder($classNamespace, $factoryNamespace);
        }

        return static::$instances[$key];
    }

    /**
     * 指定したクラスのインスタンスをランダムに生成する.
     */
    public function create(string $class, ?int $seed = null, array $overrides = [])
    {
        $factory = $this->factory($class);
        $range = $factory->range();

        return $factory->create($this, $seed ?? \mt_rand($range['min'], $range['max']), $overrides);
    }

    /**
     * 指定したクラスの重複しないインスタンスのリストを生成する.
     */
    public function createList(
        string $class,
        int $count,
        array $overrides = [],
        ?int $min = null,
        ?int $max = null
    ): Enumerable {
        $factory = $this->factory($class);
        $range = $factory->range();

        return $factory->createList(
            $this,
            $count,
            $overrides,
            $min ?? $range['min'],
            $max ?? $range['max']
        );
    }

    /**
     * 指定したインスタンスを複製する.
     */
    public function duplicate($instance, array $overrides = [])
    {
        if (\is_null($instance)) {
            return;
        }

        if ($instance instanceof Enumerable) {
            return $instance->mapWithKeys(function ($value, $key) use ($overrides): array {
                return [$key => $this->duplicate($value, $overrides)];
            });
        }

        return $this->factory(\get_class($instance))->duplicate($this, $instance, $overrides);
    }

    /**
     * 指定したクラスのファクトリを取得する.
     */
    private function factory(string $class)
    {
        if (!$this->factories->has($class)) {
            $factory = \sprintf(
                '%s%sFactory',
                $this->factoryNamespace,
                \preg_replace('/^' . \preg_quote($this->classNamespace) . '/', '', $class)
            );

            if (!\class_exists($factory)) {
                throw new \InvalidArgumentException(\sprintf(
                    'No such factory class named %s.',
                    $factory
                ));
            }

            if (!\is_subclass_of($factory, DependencyFactory::class)) {
                throw new \UnexpectedValueException(\sprintf(
                    'Factory %s is not a subclass of %s.',
                    $factory,
                    DependencyFactory::class
                ));
            }

            $this->factories->put($class, new $factory());
        }

        return $this->factories->get($class);
    }
}
