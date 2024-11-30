<?php

namespace Tests\Unit\Http\Requests\API\Authentication;

use App\Domains\Authentication\ValueObjects\TokenType;
use App\Http\Requests\API\Authentication\TokenRequest;
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
 */
class TokenRequestTest extends TestCase
{
    use CommandRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return TokenRequest::class;
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
          'type' => TokenType::ACCESS->name,
          'value' => Hash::make('password'),
          'expiresAt' => now()->addDay()->toDateTimeString(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [
          'refresh' => [
            'type' => TokenType::REFRESH->name,
            'value' => Hash::make('password'),
            'expiresAt' => now()->addDay()->toDateTimeString(),
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'type' => [
            'invalid type' => 123,
            'empty' => '',
            'invalid format' => 'invalid',
          ],
          'value' => [
            'invalid type' => 123,
            'empty' => '',
          ],
          'expiresAt' => [
            'invalid type' => 123,
            'empty' => '',
            'invalid format' => 'invalid',
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
