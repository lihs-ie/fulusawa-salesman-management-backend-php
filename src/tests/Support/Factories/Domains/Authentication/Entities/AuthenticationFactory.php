<?php

namespace Tests\Support\Factories\Domains\Authentication\Entities;

use App\Domains\Authentication\Entities\Authentication;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\ValueObjects\UserIdentifier;
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
            user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            accessToken: array_key_exists('accessToken', $overrides) ?
                $overrides['accessToken'] : $builder->create(Token::class, $seed, ['type' => TokenType::ACCESS]),
            refreshToken: array_key_exists('refreshToken', $overrides) ?
                $overrides['refreshToken'] : $builder->create(Token::class, $seed, ['type' => TokenType::REFRESH]),
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
            user: $overrides['user'] ?? $instance->user(),
            accessToken: $overrides['accessToken'] ?? $instance->accessToken(),
            refreshToken: $overrides['refreshToken'] ?? $instance->refreshToken(),
        );
    }
}
