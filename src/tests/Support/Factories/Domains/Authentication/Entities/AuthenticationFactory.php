<?php

namespace Tests\Support\Factories\Domains\Authentication\Entities;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の認証を生成するファクトリ.
 */
class AuthenticationFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Authentication
    {
        return new Authentication(
            identifier: $overrides['identifier'] ?? $builder->create(AuthenticationIdentifier::class, $seed, $overrides),
            accessToken: $overrides['accessToken'] ?? $builder->create(Token::class, $seed, $overrides),
            refreshToken: $overrides['refreshToken'] ?? $builder->create(Token::class, $seed, $overrides),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Authentication
    {
        if (!($instance instanceof Authentication)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Authentication(
            identifier: $overrides['identifier'] ?? $instance->identifier(),
            accessToken: $overrides['accessToken'] ?? $instance->accessToken(),
            refreshToken: $overrides['refreshToken'] ?? $instance->refreshToken(),
        );
    }
}
