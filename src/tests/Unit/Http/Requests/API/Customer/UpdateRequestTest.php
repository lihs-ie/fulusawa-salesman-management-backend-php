<?php

namespace Tests\Unit\Http\Requests\API\Customer;

use App\Http\Requests\API\Customer\UpdateRequest;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\CommandRequestTest;
use Tests\Unit\Http\Requests\API\Support\CommonDomainPayloadGeneratable;

/**
 * @group unit
 * @group requests
 * @group api
 * @group customer
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
          'name' => [
            'last' => Str::random(\mt_rand(1, 255)),
            'first' => Str::random(\mt_rand(1, 255))
          ],
          'address' => $this->generateAddress(),
          'phone' => $this->generatePhone(),
          'cemeteries' => Collection::times(
              \mt_rand(1, 5),
              fn (): string => Uuid::uuid7()->toString()
          )
            ->all(),
          'transactionHistories' => Collection::times(
              \mt_rand(1, 5),
              fn (): string => Uuid::uuid7()->toString()
          )
            ->all()
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
          'first name is null' => [
            'name' => [
              'last' => Str::random(\mt_rand(1, 255)),
              'first' => null
            ]
          ],
          'cemeteries is empty' => [
            'cemeteries' => []
          ],
          'transaction histories is empty' => [
            'transactionHistories' => []
          ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'cemeteries.*' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
            'contains invalid format' => [
              'invalid'
            ]
          ],
          'transactionHistories.*' => [
            'invalid type' => \mt_rand(1, 255),
            'null' => null,
            'contains invalid format' => [
              Uuid::uuid7()->toString(),
              'invalid'
            ]
          ]
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
