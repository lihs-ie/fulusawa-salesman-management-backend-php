<?php

namespace Tests\Support\Factories\Domains\User\Entities;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\User\Entities\User;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用のユーザーを生成するファクトリ.
 */
class UserFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): User
    {
        return new User(
            identifier: $overrides['identifier'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            firstName: $overrides['firstName'] ?? Str::random(\mt_rand(\abs($seed) % 10 + 1, 20)),
            lastName: $overrides['lastName'] ?? Str::random(\mt_rand(\abs($seed) % 10 + 1, 20)),
            address: $overrides['address'] ?? $builder->create(Address::class, $seed, $overrides),
            phone: $overrides['phone'] ?? $builder->create(PhoneNumber::class, $seed, $overrides),
            email: $overrides['email'] ?? $builder->create(MailAddress::class, $seed, $overrides),
            password: $overrides['password'] ?? Str::random(\mt_rand(\abs($seed) % 10 + 1, 20)),
            role: $overrides['role'] ?? $builder->create(Role::class, $seed, $overrides),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): User
    {
        if (!($instance instanceof User)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new User(
            identifier: $overrides['identifier'] ?? $instance->identifier(),
            firstName: $overrides['firstName'] ?? $instance->firstName(),
            lastName: $overrides['lastName'] ?? $instance->lastName(),
            address: $overrides['address'] ?? $instance->address(),
            phone: $overrides['phone'] ?? $instance->phone(),
            email: $overrides['email'] ?? $instance->email(),
            password: $overrides['password'] ?? $instance->password(),
            role: $overrides['role'] ?? $instance->role(),
        );
    }
}
