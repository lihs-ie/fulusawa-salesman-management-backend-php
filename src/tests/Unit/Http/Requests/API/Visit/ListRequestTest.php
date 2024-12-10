<?php

namespace Tests\Unit\Http\Requests\API\Visit;

use App\Http\Requests\API\Visit\ListRequest;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group http
 * @group requests
 * @group visit
 *
 * @coversNothing
 *
 * @internal
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
            'sort' => 'VISITED_AT_DESC',
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
            'sort' => [
                'visited_at_asc' => 'VISITED_AT_ASC',
                'null' => null,
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
    protected function getInvalidQueryPatterns(): array
    {
        return [
            'user' => [
                'invalid format' => 'invalid',
                'invalid type' => \mt_rand(1, 255),
            ],
            'sort' => [
                'invalid format' => 'invalid',
                'invalid type' => \mt_rand(1, 255),
            ],
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
