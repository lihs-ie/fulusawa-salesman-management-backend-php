<?php

namespace Tests\Unit\Http\Requests\API\TransactionHistory;

use App\Http\Requests\API\TransactionHistory\ListRequest;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group http
 * @group requests
 * @group transactionhistory
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
            'customer' => Uuid::uuid7()->toString(),
            'sort' => 'CREATED_AT_ASC',
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
            'customer' => [
                'null' => null,
            ],
            'sort' => [
                'created_at_desc' => 'CREATED_AT_DESC',
                'updated_at_asc' => 'UPDATED_AT_ASC',
                'updated_at_desc' => 'UPDATED_AT_DESC',
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
            'customer' => [
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
