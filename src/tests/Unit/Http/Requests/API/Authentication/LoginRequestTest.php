<?php

namespace Tests\Unit\Http\Requests\API\Authentication;

use App\Http\Requests\API\Authentication\LoginRequest;
use Faker;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;
use Illuminate\Support\Str;

/**
 * @group unit
 * @group requests
 * @group api
 * @group authentication
 *
 * @coversNothing
 */
class LoginRequestTest extends TestCase
{
    use CommandRequestTest;

    private Faker\Generator $faker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Faker\Factory::create();
    }

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return LoginRequest::class;
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
          'identifier' => Uuid::uuid7()->toString(),
          'email' => $this->faker->email,
          'password' => Str::random(8),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [
          'default' => []
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'identifier' => [
            'invalid type' => 123,
            'empty' => '',
            'invalid format' => 'invalid',
          ],
          'email' => [
            'invalid type' => 123,
            'empty' => '',
            'invalid format' => 'invalid',
          ],
          'password' => [
            'invalid type' => 123,
            'empty' => '',
            'too short' => Str::random(\mt_rand(1, 7)),
            'too long' =>  Str::random(256),
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
