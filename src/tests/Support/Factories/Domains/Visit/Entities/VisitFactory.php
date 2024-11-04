<?php

namespace Tests\Support\Factories\Domains\Visit\Entities;

use App\Domains\Common\ValueObjects\Address;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\ValueObjects\VisitResult;
use Carbon\CarbonImmutable;
use Tests\Support\DependencyBuilder;
use Tests\Support\DependencyFactory;

/**
 * テスト用の訪問を生成するファクトリ.
 */
class VisitFactory extends DependencyFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(DependencyBuilder $builder, int $seed, array $overrides): Visit
    {
        $result = $overrides['result'] ?? $builder->create(VisitResult::class, $seed, $overrides);
        $phone = $overrides['phone'] ?? $result === VisitResult::CONTRACT ? $builder->create(PhoneNumber::class, $seed, $overrides) : null;

        return new Visit(
            identifier: $overrides['identifier'] ?? $builder->create(VisitIdentifier::class, $seed, $overrides),
            user: $overrides['user'] ?? $builder->create(UserIdentifier::class, $seed, $overrides),
            visitedAt: $overrides['visitedAt'] ?? CarbonImmutable::now()->subDays(\abs($seed) % 10),
            address: $overrides['address'] ?? $builder->create(Address::class, $seed, $overrides),
            phone: $phone,
            hasGraveyard: $overrides['hasGraveyard'] ?? (bool) ($seed % 2),
            note: $overrides['note'] ?? null,
            result: $result
        );
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate(DependencyBuilder $builder, $instance, array $overrides): Visit
    {
        if (!($instance instanceof Visit)) {
            throw new \InvalidArgumentException('Invalid instance type.');
        }

        return new Visit(
            identifier: $overrides['identifier'] ?? $instance->identifier(),
            user: $overrides['user'] ?? $instance->user(),
            visitedAt: $overrides['visitedAt'] ?? $instance->visitedAt(),
            address: $overrides['address'] ?? $instance->address(),
            phone: $overrides['phone'] ?? $instance->phone(),
            hasGraveyard: $overrides['hasGraveyard'] ?? $instance->hasGraveyard(),
            note: $overrides['note'] ?? $instance->note(),
            result: $overrides['result'] ?? $instance->result(),
        );
    }
}
