<?php

namespace Tests\Unit\Http\Requests\API\Schedule;

use App\Domains\Schedule\Entities\Schedule;
use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Http\Requests\API\Schedule\AddRequest;
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
class AddRequestTest extends TestCase
{
    use CommandRequestTest;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return AddRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultPayload(): array
    {
        return [
          'identifier' => Uuid::uuid7()->toString(),
          'participants' => Collection::times(
              \mt_rand(1, 10),
              fn (): string => Uuid::uuid7()->toString()
          )->all(),
          'creator' => Uuid::uuid7()->toString(),
          'updater' => Uuid::uuid7()->toString(),
          'customer' => Uuid::uuid7()->toString(),
          'content' => [
            'title' => Str::random(\mt_rand(1, 255)),
            'description' => Str::random(\mt_rand(1, 1000)),
          ],
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidPayloadPatterns(): array
    {
        return [
          'participants' => [
            'empty' => []
          ],
          'customer' => [
            'null' => null,
          ],
          'content' => [
            'description is null' => [
              'title' => Str::random(\mt_rand(1, 255)),
              'description' => null,
            ],
            'description is empty' => [
              'title' => Str::random(\mt_rand(1, 255)),
              'description' => '',
            ],
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
          'identifier' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'participants' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'participants.*' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'too long' =>  Collection::times(
                Schedule::MAX_PARTICIPANTS + 1,
                fn (): string => Uuid::uuid7()->toString()
            )->all(),
          ],
          'creator' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
      ],
          'updater' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
      ],
          'customer' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
      ],
          'content' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
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
        return [];
    }
}
