<?php

namespace Tests\Unit\Http\Requests\API\Cemetery;

use App\Http\Requests\API\Cemetery\ListRequest;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group cemetery
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
          'customer' => Uuid::uuid7()->toString()
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
          'customer' => [
            'empty' => '',
            'null' => null,
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
          'customer' => [
            'invalid format' => 'invalid',
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
