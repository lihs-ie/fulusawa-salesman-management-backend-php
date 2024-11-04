<?php

namespace Tests\Support\Factories\Domains\TransactionHistory\Entities;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\Domains\User\ValueObjects\UserIdentifier;
use Carbon\CarbonImmutable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の取引履歴を生成するファクトリ.
 */
class TransactionHistoryFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): TransactionHistory
    {
        return new TransactionHistory(
            identifier: $overrides['identifier'] ?? $builder->create(TransactionHistoryIdentifier::class, $seed, $overrides),
            customer: $overrides['customer'] ?? $builder->create(CustomerIdentifier::class, $seed, $overrides),
            user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            type: $overrides['type'] ?? $builder->create(TransactionType::class, $seed, $overrides),
            description: $overrides['description'] ?? null,
            date: $overrides['date'] ?? CarbonImmutable::now()->subDays(\abs($seed % 100)),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): TransactionHistory
    {
        if (!($instance instanceof TransactionHistory)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new TransactionHistory(
            identifier: $overrides['identifier'] ?? $instance->identifier(),
            customer: $overrides['customer'] ?? $instance->customer(),
            user: $overrides['user'] ?? $instance->salesman(),
            type: $overrides['type'] ?? $instance->type(),
            description: $overrides['description'] ?? $instance->description(),
            date: $overrides['date'] ?? $instance->date(),
        );
    }
}
