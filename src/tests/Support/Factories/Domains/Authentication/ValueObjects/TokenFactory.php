<?php

namespace Tests\Support\Factories\Domains\Authentication\ValueObjects;

use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のトークンを生成するファクトリ.
 */
class TokenFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Token
    {
        $valuePrefix = $overrides['valuePrefix'] ?? Uuid::uuid7(CarbonImmutable::now()->subDay(\abs($seed % 10)))->toString();
        $valueSuffix = $overrides['valueSuffix'] ?? Hash::make(\abs($seed) * 1000);

        return new Token(
            type: $overrides['type'] ?? $builder->create(TokenType::class, $seed, $overrides),
            value: $overrides['value'] ?? "{$valuePrefix}|{$valueSuffix}",
            expiresAt: $overrides['expiresAt'] ?? CarbonImmutable::now()->addMinutes(\abs($seed % 2 * 10))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Token
    {
        if (!($instance instanceof Token)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Token(
            type: $overrides['type'] ?? $instance->type(),
            value: $overrides['value'] ?? $instance->value(),
            expiresAt: $overrides['expiresAt'] ?? $instance->expiresAt()
        );
    }
}
