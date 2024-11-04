<?php

namespace Tests\Support\Factories;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のenum値を生成するファクトリの基底実装.
 */
abstract class EnumFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): \UnitEnum
    {
        $class = $this->target();

        if (isset($overrides['value'])) {
            return $class::from($overrides['value']);
        }

        $candidates = Collection::make($class::cases());

        $index = $seed % $candidates->count();

        return $candidates->values()->get($index);
    }

    /**
     * {@inheritdoc}
     */
    public function createList(
        DependencyBuilder $builder,
        int $count,
        array $overrides,
        int $min,
        int $max
    ): Enumerable {
        $class = $this->target();

        $candidates = Collection::make($class::cases());

        if ($count > $candidates->count()) {
            throw new \InvalidArgumentException(\sprintf(
                'Class %s does not have candidates more than %d. %d is required.',
                $class,
                $candidates->count(),
                $count
            ));
        }

        return $candidates->random($count);
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): \UnitEnum
    {
        $class = $this->target();

        if (!$instance instanceof $class) {
            throw new \InvalidArgumentException('Invalid type of instance.');
        }

        return $instance;
    }

    /**
     * 対象のFQCNを取得する.
     */
    abstract protected function target(): string;
}
