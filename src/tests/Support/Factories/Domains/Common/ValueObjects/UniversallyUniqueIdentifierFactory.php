<?php

namespace Tests\Support\Factories\Domains\Common\ValueObjects;

use App\Domains\Common\ValueObjects\UniversallyUniqueIdentifier;
use Ramsey\Uuid\Uuid;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のUUIDを使用する識別子を生成する基底ファクトリ.
 */
abstract class UniversallyUniqueIdentifierFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides)
    {
        $class = $this->target();

        return new $class($overrides['value'] ?? Uuid::uuid7()->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides)
    {
        $class = $this->target();

        if (!$instance instanceof UniversallyUniqueIdentifier) {
            throw new \InvalidArgumentException('Invalid type of instance.');
        }

        return new $class($overrides['value'] ?? $instance->value());
    }

    /**
     * 対象のFQCNを取得する.
     */
    abstract protected function target(): string;
}
