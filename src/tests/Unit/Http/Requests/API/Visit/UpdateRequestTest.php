<?php

namespace Tests\Unit\Http\Requests\API\Visit;

use App\Domains\Visit\ValueObjects\VisitResult;
use App\Http\Requests\API\Visit\UpdateRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;
use Tests\Unit\Http\Requests\API\Support\CommonDomainPayloadGeneratable;

/**
 * @group unit
 * @group http
 * @group requests
 * @group visit
 *
 * @coversNothing
 */
class UpdateRequestTest extends TestCase
{
    use CommandRequestTest;
    use CommonDomainPayloadGeneratable;

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
          'visitedAt' => CarbonImmutable::now()->toAtomString(),
          'address' => $this->generateAddress(),
          'phone' => $this->generatePhone(),
          'hasGraveyard' => (bool)\mt_rand(0, 1),
          'note' => Str::random(\mt_rand(1, 1000)),
          'result' => Collection::make(VisitResult::cases())->random()->name,
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
          'phone' => [
            'null' => null,
            'empty' => '',
          ],
          'note' => [
            'null' => null,
            'empty' => '',
          ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'address' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'phone' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
          ],
          'hasGraveyard' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'note' => [
            'invalid type' => \mt_rand(1, 255),
            'too long' => Str::random(1001),
          ],
          'result' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
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
