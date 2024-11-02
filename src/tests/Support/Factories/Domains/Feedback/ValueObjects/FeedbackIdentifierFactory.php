<?php

namespace Tests\Support\Factories\Domains\Feedback\ValueObjects;

use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use Tests\Support\Factories\Domains\Common\ValueObjects\UniversallyUniqueIdentifierFactory;

/**
 * テスト用のフィードバック識別子を生成するファクトリ.
 */
class FeedbackIdentifierFactory extends UniversallyUniqueIdentifierFactory
{
    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return FeedbackIdentifier::class;
    }
}
