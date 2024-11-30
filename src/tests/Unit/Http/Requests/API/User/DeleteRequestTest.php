<?php

namespace Tests\Unit\Http\Requests\API\User;

use App\Http\Requests\API\User\DeleteRequest;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;

/**
 * @group unit
 * @group http
 * @group requests
 * @group user
 *
 * @coversNothing
 */
class DeleteRequestTest extends TestCase
{
    use CommandRequestTest;

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
    protected function createDefaultPayload(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultRoute(): array
    {
        return [
            'identifier' => Uuid::uuid7()->toString()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [];
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
                'invalid type' => [\mt_rand(1, 255)],
                'invalid format' => ['invalid'],
                'null' => [null],
                'empty' => [''],
            ]
        ];
    }
}
