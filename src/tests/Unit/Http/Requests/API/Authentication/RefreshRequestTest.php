<?php

namespace Tests\Unit\Http\Requests\API\Authentication;

use App\Domains\Authentication\ValueObjects\TokenType;
use App\Http\Requests\API\Authentication\RefreshRequest;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;

/**
 * @group unit
 * @group http
 * @group requests
 * @group api
 * @group authentication
 *
 * @coversNothing
 *
 * @internal
 */
class RefreshRequestTest extends TestCase
{
    use CommandRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return RefreshRequest::class;
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
    protected function getValidRoutePatterns(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultPayload(): array
    {
        return [
            'type' => TokenType::REFRESH->name,
            'value' => Hash::make('password'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [
            'default' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
            'type' => [
                'invalid type' => TokenType::ACCESS->name,
                'integer' => 123,
                'empty' => '',
                'invalid format' => 'invalid',
            ],
            'value' => [
                'invalid type' => 123,
                'empty' => '',
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
