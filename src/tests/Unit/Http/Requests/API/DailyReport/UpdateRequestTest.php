<?php

namespace Tests\Unit\Http\Requests\API\DailyReport;

use App\Http\Requests\API\DailyReport\UpdateRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group dailyreport
 *
 * @coversNothing
 */
class UpdateRequestTest extends TestCase
{
    use CommandRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return UpdateRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultPayload(): array
    {
        return [
          'user' => Uuid::uuid7()->toString(),
          'date' => CarbonImmutable::now()->toAtomString(),
          'schedules' => Collection::times(\mt_rand(1, 5), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 5), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultRoute(): array
    {
        return [
          'identifier' => Uuid::uuid7()->toString(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [
          'schedules' => [
            'empty' => [],
          ],
          'visits' => [
            'empty' => [],
          ],
          'isSubmitted' => [
            'false' => false,
          ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'user' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'date' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'schedules' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
          ],
          'schedules.*' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
            'contains invalid format' => [
              Uuid::uuid7()->toString(),
              'invalid',
            ],
          ],
          'visits' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
          ],
          'visits.*' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
            'contains invalid format' => [
              Uuid::uuid7()->toString(),
              'invalid',
            ],
          ],
          'isSubmitted' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
            'empty' => '',
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
        return [
          'identifier' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
        ];
    }
}
