<?php

namespace Tests\Unit\Http\Requests\API\TransactionHistory;

use App\Domains\TransactionHistory\ValueObjects\FrequencyType;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\Http\Requests\API\TransactionHistory\AddRequest;
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
 * @group transactionhistory
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
          'user' => Uuid::uuid7()->toString(),
          'customer' => Uuid::uuid7()->toString(),
          'type' => Collection::make(TransactionType::cases())->random()->name,
          'description' => Str::random(\mt_rand(1, 1000)),
          'date' => CarbonImmutable::now()->toAtomString(),
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
          'description' => [
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
          'identifier' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
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
          'type' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'description' => [
            'invalid type' => \mt_rand(1, 255),
            'too long' => \str_repeat('a', 1001),
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
