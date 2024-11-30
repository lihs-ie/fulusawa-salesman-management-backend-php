<?php

namespace Tests\Unit\Http\Requests\API\Customer;

use App\Http\Requests\API\Customer\DeleteRequest;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group customer
 *
 * @coversNothing
 */
class DeleteRequestTest extends TestCase
{
    use QueryRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return DeleteRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultQuery(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultRoute(): array
    {
        return ['identifier' => Uuid::uuid7()->toString()];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidQueryPatterns(): array
    {
        return ['default' => []];
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidRoutePatterns(): array
    {
        return [
          'identifier' => [
            'null' => [null],
            'empty' => [''],
            'invalid' => ['invalid']
          ]
        ];
    }
}
