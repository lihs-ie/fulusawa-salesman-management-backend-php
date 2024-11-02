<?php

namespace Tests\Support\Factories\Domains\Customer\Entities;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Customer\Entities\Customer;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の顧客を生成するファクトリ.
 */
class CustomerFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Customer
    {
        return new Customer(
            identifier: $builder->create(CustomerIdentifier::class, $seed, $overrides),
            lastName: $overrides['lastName'] ?? Str::random(\mt_rand(\abs($seed % 10 + 1), 20)),
            firstName: $overrides['firstName'] ?? Str::random(\mt_rand(\abs($seed % 10 + 1), 20)),
            address: $overrides['address'] ?? $builder->create(Address::class, $seed, $overrides),
            phone: $overrides['phone'] ?? $builder->create(PhoneNumber::class, $seed, $overrides),
            cemeteries: $overrides['cemeteries'] ?? new Collection(),
            transactionHistories: $overrides['transactionHistories'] ?? new Collection(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Customer
    {
        if (!($instance instanceof Customer)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Customer(
            identifier: $overrides['identifier'] ?? $builder->duplicate($instance->identifier(), $overrides),
            lastName: $overrides['lastName'] ?? $instance->lastName(),
            firstName: $overrides['firstName'] ?? $instance->firstName(),
            address: $overrides['address'] ?? $builder->duplicate($instance->address(), $overrides),
            phone: $overrides['phone'] ?? $builder->duplicate($instance->phone(), $overrides),
            cemeteries: $overrides['cemeteries'] ?? $instance->cemeteries(),
            transactionHistories: $overrides['transactionHistories'] ?? $instance->transactionHistories(),
        );
    }
}
