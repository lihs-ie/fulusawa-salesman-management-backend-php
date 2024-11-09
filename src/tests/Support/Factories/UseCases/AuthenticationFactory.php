<?php

namespace Tests\Support\Factories\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\UseCases\Authentication;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の認証ユースケースを生成するファクトリ.
 */
class AuthenticationFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Authentication
    {
        $instances = $overrides['instances'] ?? null;

        return new Authentication(
            repository: $builder->create(AuthenticationRepository::class, $seed, ['instances' => $instances]),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Authentication
    {
        throw new \BadMethodCallException('UseCase cannot be duplicated.');
    }
}
