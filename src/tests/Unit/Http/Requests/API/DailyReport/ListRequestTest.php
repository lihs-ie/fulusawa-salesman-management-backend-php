<?php

namespace Tests\Unit\Http\Requests\API\DailyReport;

use App\Http\Requests\API\DailyReport\ListRequest;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group dailyreport
 *
 * @coversNothing
 */
class ListRequestTest extends TestCase
{
    use QueryRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return ListRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultQuery(): array
    {
        return [
          'user' => Uuid::uuid7()->toString(),
          'date' => [
            'start' => CarbonImmutable::now()->subDays(7)->toAtomString(),
            'end' => CarbonImmutable::now()->toAtomString()
          ],
          'isSubmitted' => false
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
    protected function getValidQueryPatterns(): array
    {
        return [
          'user' => [
            'null' => null,
          ],
          'date' => [
            'both null' => [
              'start' => null,
              'end' => null
            ],
            'start null' => [
              'start' => null,
              'end' => CarbonImmutable::now()->toAtomString()
            ],
            'end null' => [
              'start' => CarbonImmutable::now()->subDays(7)->toAtomString(),
              'end' => null
            ],
          ],
          'isSubmitted' => [
            'null' => null,
            'true' => true,
          ]
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
    protected function getInvalidQueryPatterns(): array
    {
        return [
          'user' => [
            'invalid format' => 'invalid',
            'invalid type' => \mt_rand(1, 255)
          ],
          'date.start' => [
            'invalid format' => 'invalid',
            'invalid type' => \mt_rand(1, 255),
          ],
          'date.end' => [
            'invalid format' => 'invalid',
            'invalid type' => \mt_rand(1, 255),
            'start after end' => [
              'start' => CarbonImmutable::now()->toAtomString(),
              'end' => CarbonImmutable::now()->subDays(7)->toAtomString()
            ]
          ],
          'isSubmitted' => [
            'invalid type' => \mt_rand(1, 255)
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidRoutePatterns(): array
    {
        return [];
    }
}
