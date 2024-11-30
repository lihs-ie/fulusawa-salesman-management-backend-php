<?php

namespace Tests\Unit\Http\Requests\API\Cemetery;

use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Http\Requests\API\Cemetery\UpdateRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;

/**
 * @group unit
 * @group requests
 * @group api
 * @group cemetery
 *
 * @coversNothing
 */
class UpdateRequestTest extends TestCase
{
    use CommandRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return UpdateRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultPayload(): array
    {
        return [
          'customer' => Uuid::uuid7()->toString(),
          'name' => Str::random(\mt_rand(1, 255)),
          'type' => Collection::make(CemeteryType::cases())->random()->name,
          'construction' => CarbonImmutable::now()->toAtomString(),
          'inHouse' => (bool) \mt_rand(0, 1),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultRoute(): array
    {
        return [
          'identifier' => Uuid::uuid7()->toString(),
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
          'customer' => [
            'invalid type' => \mt_rand(1, 255),
            'empty' => '',
            'invalid format' => 'invalid',
            'null' => null
          ],
          'name' => [
            'invalid type' => \mt_rand(1, 255),
            'empty' => '',
            'too long' => Str::random(256),
            'null' => null
          ],
          'type' => [
            'invalid type' => \mt_rand(1, 255),
            'empty' => '',
            'invalid format' => 'invalid',
            'null' => null
          ],
          'construction' => [
            'invalid type' => \mt_rand(1, 255),
            'empty' => '',
            'invalid format' => 'invalid',
            'null' => null
          ],
          'inHouse' => [
            'invalid type' => \mt_rand(1, 255),
            'empty' => '',
            'invalid format' => 'invalid',
            'null' => null
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
    protected function getInvalidRoutePatterns(): array
    {
        return [
          'identifier' => [
            'invalid type' => \mt_rand(1, 255),
            'empty' => '',
            'invalid format' => 'invalid',
            'null' => null
          ],
        ];
    }
}
