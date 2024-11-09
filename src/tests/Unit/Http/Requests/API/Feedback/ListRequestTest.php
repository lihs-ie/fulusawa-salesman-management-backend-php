<?php

namespace Tests\Unit\Http\Requests\API\Feedback;

use App\Http\Requests\API\Feedback\ListRequest;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group feedback
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
          'status' => 'WAITING',
          'type' => 'IMPROVEMENT',
          'sort' => 'CREATED_AT_DESC'
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
          'status' => [
            'null' => null,
            'in_progress' => 'IN_PROGRESS',
            'completed' => 'COMPLETED',
            'not_necessary' => 'NOT_NECESSARY'
          ],
          'type' => [
            'null' => null,
            'problem' => 'PROBLEM',
            'question' => 'QUESTION',
            'other' => 'OTHER'
          ],
          'sort' => [
            'null' => null,
            'created_at_asc' => 'CREATED_AT_ASC',
            'updated_at_desc' => 'UPDATED_AT_DESC',
            'updated_at_asc' => 'UPDATED_AT_ASC'
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
          'status' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
          ],
          'type' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
          ],
          'sort' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
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
