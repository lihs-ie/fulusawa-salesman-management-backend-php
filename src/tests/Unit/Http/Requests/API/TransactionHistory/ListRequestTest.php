<?php

namespace Tests\Unit\Http\Requests\API\TransactionHistory;

use App\Http\Requests\API\Schedule\ListRequest;
use Carbon\CarbonImmutable;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group http
 * @group requests
 * @group transactionhistory
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
          'status' => 'IN_COMPLETE',
          'date' => [
            'start' => CarbonImmutable::now()->toAtomString(),
            'end' => CarbonImmutable::now()->toAtomString()
          ]
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidRoutePatterns(): array
    {
        return [
          'status' => [
            'completed' => 'COMPLETED',
            'in_progress' => 'IN_PROGRESS',
            'null' => null
          ],
          'date' => [
            'null' => null,
            'start null' => [
              'start' => null,
              'end' => CarbonImmutable::now()->toAtomString()
            ],
            'end null' => [
              'start' => CarbonImmutable::now()->toAtomString(),
              'end' => null
            ],
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidQueryPatterns(): array
    {
        return [
          'status' => [
            'invalid' => 'invalid',
            'invalid type' => \mt_rand(1, 255),
          ],
          'date' => [
            'invalid' => [
              'start' => 'invalid',
              'end' => 'invalid'
            ],
            'start after end' => [
              'start' => CarbonImmutable::now()->toAtomString(),
              'end' => CarbonImmutable::now()->subSeconds(1)->toAtomString()
            ]
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
