<?php

namespace Tests\Unit\Http\Requests\API\Customer;

use App\Http\Requests\API\Customer\ListRequest;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Http\Requests\API\QueryRequestTest;
use Tests\Unit\Http\Requests\API\Support\CommonDomainPayloadGeneratable;

/**
 * @group unit
 * @group requests
 * @group api
 * @group cemetery
 *
 * @coversNothing
 */
class ListRequestTest extends TestCase
{
    use QueryRequestTest;
    use CommonDomainPayloadGeneratable;

    /**
     * {@inheritdoc}
     */
    protected function target(): string
    {
        return ListRequest::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultQuery(): array
    {
        return [
          'name' => Str::random(\mt_rand(1, 255)),
          'phone' => $this->generatePhone(),
          'postalCode' => $this->generatePostalCode(),
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
    protected function getValidQueryPatterns(): array
    {
        return [
          'name' => [
            'null' => null,
          ],
          'phone' => [
            'empty' => '',
            'null' => null,
          ],
          'postalCode' => [
            'empty' => '',
            'null' => null,
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
    protected function getInvalidQueryPatterns(): array
    {
        return [
          'name' => [
            'too long' => Str::random(256),
          ],
          'phone' => [
            'invalid format' => 'invalid',
            'invalid type' => \mt_rand(1, 255),
            'no first' => [
              'last' => Str::random(\mt_rand(1, 255)),
            ],
            'no last' => [
              'first' => Str::random(\mt_rand(1, 255)),
            ],
          ],
          'postalCode' => [
            'invalid format' => 'invalid',
            'invalid type' => \mt_rand(1, 255),
            'no first' => [
              'last' => Str::random(\mt_rand(1, 255)),
            ],
            'no last' => [
              'first' => Str::random(\mt_rand(1, 255)),
            ]
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
