<?php

namespace Tests\Support\Factories\Domains\Customer\ValueObjects;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用の顧客識別子を生成するファクトリ.
 */
class CustomerIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return CustomerIdentifier::class;
    }
}
