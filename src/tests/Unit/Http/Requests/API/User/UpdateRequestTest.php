<?php

namespace Tests\Unit\Http\Requests\API\User;

use App\Domains\User\ValueObjects\Role;
use App\Http\Requests\API\User\UpdateRequest;
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
 * @group user
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
            'first' => Str::random(\mt_rand(1, 255)),
            'last' => Str::random(\mt_rand(1, 255)),
          ],
          'address' => $this->generateAddress(),
          'email' => $this->generateEmail(),
          'phone' => $this->generatePhone(),
          'password' => 'Password!1',
          'role' => Collection::make(Role::cases())->random()->name,
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
          'default' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getInvalidPayloadPatterns(): array
    {
        return [
          'name' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'name.first' => [
            'too long' => Str::random(256),
            'null' => null,
            'empty' => '',
          ],
          'name.last' => [
            'too long' => Str::random(256),
            'null' => null,
            'empty' => '',
          ],
          'address' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'email' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'phone' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'password' => [
            'invalid type' => \mt_rand(1, 255),
            'invalid format' => 'invalid',
            'null' => null,
            'empty' => '',
          ],
          'role' => [
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
