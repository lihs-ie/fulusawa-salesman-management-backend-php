<?php

namespace Tests\Unit\Http\Requests\API\Feedback;

use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\Http\Requests\API\Feedback\PersistRequest;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group feedback
 *
 * @coversNothing
 */
class PersistRequestTest extends TestCase
{
    use CommandRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return PersistRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultPayload(): array
    {
        return [
          'identifier' => Uuid::uuid7()->toString(),
          'type' => FeedbackType::IMPROVEMENT->name,
          'status' => FeedbackStatus::WAITING->name,
          'content' => Str::random(\mt_rand(1, 1000)),
          'createdAt' => CarbonImmutable::now()->toAtomString(),
          'updatedAt' => CarbonImmutable::now()->toAtomString(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultRoute(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [
          'type' => [
            'problem' => FeedbackType::PROBLEM->name,
            'question' => FeedbackType::QUESTION->name,
            'other' => FeedbackType::OTHER->name,
          ],
          'status' => [
            'in_progress' => FeedbackStatus::IN_PROGRESS->name,
            'completed' => FeedbackStatus::COMPLETED->name,
            'not_necessary' => FeedbackStatus::NOT_NECESSARY->name,
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'identifier' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'type' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'status' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'content' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
            'empty' => '',
          ],
          'createdAt' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'updatedAt' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
            'before createdAt' => [
              'createdAt' => CarbonImmutable::now()->toAtomString(),
              'updatedAt' => CarbonImmutable::now()->subSeconds(1)->toAtomString(),
            ],
          ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidRoutePatterns(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidRoutePatterns(): array
    {
        return [];
    }
}
