<?php

namespace Tests\Unit\Http\Requests\API\Schedule;

use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Http\Requests\API\Schedule\UpdateRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;

/**
 * @group unit
 * @group http
 * @group requests
 * @group schedule
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
          'user' => Uuid::uuid7()->toString(),
          'customer' => Uuid::uuid7()->toString(),
          'title' => Str::random(\mt_rand(1, 255)),
          'description' => Str::random(\mt_rand(1, 1000)),
          'date' => [
            'start' => CarbonImmutable::now()->toAtomString(),
            'end' => CarbonImmutable::now()->toAtomString()
          ],
          'status' => Collection::make(ScheduleStatus::cases())->random()->name,
          'repeatFrequency' => [
            'type' => Collection::make(FrequencyType::cases())->random()->name,
            'interval' => \mt_rand(1, 10)
          ]
        ];
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
        return [
          'customer' => [
            'null' => null,
          ],
          'description' => [
            'null' => null,
            'empty' => '',
          ],
          'repeatFrequency' => [
            'null' => null,
            'empty' => ''
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'user' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'customer' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
          ],
          'date' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'status' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'repeatFrequency' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'contains invalid type' => [
              'type' => \mt_rand(1, 255),
              'interval' => \mt_rand(1, 10)
            ],
            'contains invalid interval' => [
              'type' => Collection::make(FrequencyType::cases())->random()->name,
              'interval' => 0
            ],
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
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
        ];
    }
}
