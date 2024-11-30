<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\User\ValueObjects\Role;
use App\Infrastructures\Authentication\Models\Authentication;
use App\Infrastructures\Cemetery\Models\Cemetery as Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;
use Tests\TestCase;

/**
 * @group feature
 * @group http
 * @group controller
 * @group api
 * @group cemetery
 *
 * @coversNothing
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CemeteryControllerTest extends TestCase
{
    use DependencyBuildable;
    use FactoryResolvable;
    use RefreshDatabase;

    /**
     * テストに使用するレコード.
     */
    private Enumerable|null $records;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->records = $this->createRecords();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        $this->records = null;

        parent::tearDown();
    }

    /**
     * @testdox testListReturnsSuccessfulResponseWithEmptyConditions 墓地情報一覧取得APIで条件が空の場合に正常なレスポンスが返却されること.
     */
    public function testListReturnsSuccessfulResponseWithEmptyConditions(): void
    {
        $expected = [
          'cemeteries' =>  $this->records
            ->map(
                fn (Record $record): array => [
                'identifier' => $record->identifier,
                'customer' => $record->customer,
                'name' => $record->name,
                'type' => $record->type,
                'construction' => $record->construction->format(\DATE_ATOM),
                'inHouse' => $record->in_house,
          ]
            )->toArray()
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitListAPI($accessToken)
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListReturnsSuccessfulResponseWithConditions 墓地情報一覧取得APIで条件が指定された場合に正常なレスポンスが返却されること.
     */
    public function testListReturnsSuccessfulResponseWithConditions(): void
    {
        $record = $this->pickRecord();

        $conditions = [
          'customer' => $record->customer,
        ];

        $expected = [
          'cemeteries' =>  $this->records
            ->filter(fn (Record $record): bool => $record->customer === $conditions['customer'])
            ->map(
                fn (Record $record): array => [
                'identifier' => $record->identifier,
                'customer' => $record->customer,
                'name' => $record->name,
                'type' => $record->type,
                'construction' => $record->construction->format(\DATE_ATOM),
                'inHouse' => $record->in_house,
          ]
            )
            ->values()
            ->all()
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitListAPI($accessToken, $conditions)
        );

        $response->assertSuccessful();
        $response->assertJson($expected, true);
    }

    /**
     * @testdox testListReturnsUnprocessableEntityWithInvalidConditions 墓地情報一覧取得APIで不正な条件が指定された場合にUnprocessableEntityが返却されること.
     */
    public function testListReturnsUnprocessableEntityWithInvalidConditions(): void
    {
        $invalid = [
          'customer' => 'invalid',
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitListAPI($accessToken, $invalid)
        );

        $response->assertStatus(422);
    }

    /**
     * @testdox testListReturnsUnAuthorizedWithOutLogin 墓地情報一覧取得APIで未認証の場合にUnAuthorizedが返却されること.
     */
    public function testListReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitListAPI();

        $response->assertUnauthorized();
    }

    /**
     * @testdox testFindReturnsSuccessfulResponse 墓地情報取得APIで正常なリクエストが送信された場合に正常なレスポンスが返却されること.
     */
    public function testFindReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord();

        $expected = [
          'cemetery' => [
            'identifier' => $record->identifier,
            'customer' => $record->customer,
            'name' => $record->name,
            'type' => $record->type,
            'construction' => $record->construction->format(\DATE_ATOM),
            'inHouse' => $record->in_house,
          ]
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($record->identifier, $accessToken)
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testFindReturnsUnprocessableEntityWithInvalidIdentifier 墓地情報取得APIで不正な識別子が指定された場合にUnprocessableEntityが返却されること.
     */
    public function testFindReturnsUnprocessableEntityWithInvalidIdentifier(): void
    {
        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitFindAPI('invalid', $accessToken)
        );

        $response->assertStatus(422);
    }

    /**
     * @testdox testFindReturnsNotFoundWithNotExistsIdentifier 墓地情報取得APIで存在しない識別子が指定された場合にNotFoundが返却されること.
     */
    public function testFindReturnsNotFoundWithNotExistsIdentifier(): void
    {
        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindReturnsUnAuthorizedWithOutLogin 墓地情報取得APIで未認証の場合にUnAuthorizedが返却されること.
     */
    public function testFindReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitFindAPI(Uuid::uuid7()->toString());

        $response->assertUnauthorized();
    }

    /**
     * @testdox testCreateReturnsSuccessfulResponse 墓地情報作成APIで正常なリクエストが送信された場合に正常なレスポンスが返却されること.
     */
    public function testCreateReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
          'customer' => $this->builder()->create(
              CustomerIdentifier::class,
              null,
              ['value' => $record->customer]
          ),
        ]);

        $payload = [
          'identifier' => $entity->identifier()->value(),
          'customer' => $entity->customer()->value(),
          'name' => $entity->name(),
          'type' => $entity->type()->name,
          'construction' => $entity->construction()->toAtomString(),
          'inHouse' => $entity->inHouse(),
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI($payload, $accessToken)
        );

        $response->assertSuccessful();
        $response->assertStatus(201);
        $this->assertDatabaseHas('cemeteries', [
          'identifier' => $entity->identifier()->value(),
          'customer' => $entity->customer()->value(),
          'name' => $entity->name(),
          'type' => $entity->type()->name,
          'construction' => $entity->construction()->toAtomString(),
          'in_house' => $entity->inHouse(),
        ]);
    }

    /**
     * @testdox testCreateReturnsUnprocessableEntityWithInvalidPayload 墓地情報作成APIで不正なペイロードが指定された場合にUnprocessableEntityが返却されること.
     */
    public function testCreateReturnsUnprocessableEntityWithInvalidPayload(): void
    {
        $payload = [
          'identifier' => 'invalid',
          'customer' => 'invalid',
          'name' => 'invalid',
          'type' => 'invalid',
          'construction' => 'invalid',
          'inHouse' => 'invalid',
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI($payload, $accessToken)
        );

        $response->assertStatus(422);
    }

    /**
     * @textdox testCreateReturnsBadRequestWithNonExistenceCustomer 墓地情報作成APIで存在しない顧客が指定された場合にBadRequestが返却されること.
     */
    public function testCreateReturnsBadRequestWithNonExistenceCustomer(): void
    {
        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'customer' => Uuid::uuid7()->toString(),
          'name' => 'name',
          'type' => CemeteryType::INDIVIDUAL->name,
          'construction' => '2021-01-01T00:00:00+00:00',
          'inHouse' => true,
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI($payload, $accessToken)
        );

        $response->assertStatus(400);
    }

    /**
     * @testdox testCreateReturnsUnAuthorizedWithOutLogin 墓地情報作成APIで未認証の場合にUnAuthorizedが返却されること.
     */
    public function testCreateReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitCreateAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateReturnsSuccessfulResponse 墓地情報更新APIで正常なリクエストが送信された場合に正常なレスポンスが返却されること.
     */
    public function testUpdateReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
          'identifier' => $this->builder()->create(
              CemeteryIdentifier::class,
              null,
              ['value' => $record->identifier]
          ),
          'customer' => $this->builder()->create(
              CustomerIdentifier::class,
              null,
              ['value' => $record->customer]
          ),
        ]);

        $payload = [
          'identifier' => $entity->identifier()->value(),
          'customer' => $entity->customer()->value(),
          'name' => $entity->name(),
          'type' => $entity->type()->name,
          'construction' => $entity->construction()->toAtomString(),
          'inHouse' => $entity->inHouse(),
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI(
                $record->identifier,
                $payload,
                $accessToken
            )
        );

        $response->assertSuccessful();
        $response->assertStatus(204);
        $this->assertDatabaseHas('cemeteries', [
          'identifier' => $entity->identifier()->value(),
          'customer' => $entity->customer()->value(),
          'name' => $entity->name(),
          'type' => $entity->type()->name,
          'construction' => $entity->construction()->toAtomString(),
          'in_house' => $entity->inHouse(),
        ]);
    }

    /**
     * @testdox testUpdateReturnsUnprocessableEntityWithInvalidPayload 墓地情報更新APIで不正なペイロードが指定された場合にUnprocessableEntityが返却されること.
     */
    public function testUpdateReturnsUnprocessableEntityWithInvalidPayload(): void
    {
        $record = $this->pickRecord();

        $payload = [
          'identifier' => 'invalid',
          'customer' => 'invalid',
          'name' => 'invalid',
          'type' => 'invalid',
          'construction' => 'invalid',
          'inHouse' => 'invalid',
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($record->identifier, $payload, $accessToken)
        );

        $response->assertStatus(422);
    }

    /**
     * @testdox testUpdateReturnsBadRequestWithNonExistenceCustomer 墓地情報更新APIで存在しない顧客が指定された場合にBadRequestが返却されること.
     */
    public function testUpdateReturnsBadRequestWithNonExistenceCustomer(): void
    {
        $record = $this->pickRecord();

        $payload = [
          'identifier' => $record->identifier,
          'customer' => Uuid::uuid7()->toString(),
          'name' => 'name',
          'type' => CemeteryType::INDIVIDUAL->name,
          'construction' => '2021-01-01T00:00:00+00:00',
          'inHouse' => true,
        ];

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($record->identifier, $payload, $accessToken)
        );

        $response->assertStatus(400);
    }

    /**
     * @testdox testUpdateReturnsUnAuthorizedWithOutLogin 墓地情報更新APIで未認証の場合にUnAuthorizedが返却されること.
     */
    public function testUpdateReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitUpdateAPI(Uuid::uuid7()->toString(), []);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteReturnsSuccessfulResponse 墓地情報削除APIで正常なリクエストが送信された場合に正常なレスポンスが返却されること.
     */
    public function testDeleteReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord();

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken)
        );

        $response->assertSuccessful();
        $response->assertStatus(200);
        $this->assertDatabaseMissing('cemeteries', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteReturnsUnprocessableEntityWithInvalidIdentifier 墓地情報削除APIで不正な識別子が指定された場合にUnprocessableEntityが返却されること.
     */
    public function testDeleteReturnsUnprocessableEntityWithInvalidIdentifier(): void
    {
        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI('invalid', $accessToken)
        );

        $response->assertStatus(422);
    }

    /**
     * @testdox testDeleteReturnsNotFoundWithNotExistsIdentifier 墓地情報削除APIで存在しない識別子が指定された場合にNotFoundが返却されること.
     */
    public function testDeleteReturnsNotFoundWithNotExistsIdentifier(): void
    {
        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(Uuid::uuid7()->toString(), $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testDeleteReturnsUnAuthorizedWithAdminUser 墓地情報削除APIで一般ユーザーの場合にUnAuthorizedが返却されること.
     */
    public function testDeleteReturnsUnAuthorizedWithNormalUser(): void
    {
        $record = $this->pickRecord();

        $response = $this->withLogin(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testDeleteReturnsUnAuthorizedWithOutLogin 墓地情報削除APIで未認証の場合にUnAuthorizedが返却されること.
     */
    public function testDeleteReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitDeleteAPI(Uuid::uuid7()->toString());

        $response->assertUnauthorized();
    }

    /**
     * テストに使用するレコードを作成するへルパ.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * 生成したレコードをランダムで取得するへルパ.
     */
    private function pickRecord(): Record
    {
        return $this->records->random();
    }

    /**
     * 認証情報を付与してAPIを実行する.
     */
    private function withLogin(\Closure $callback, Role $role = Role::ADMIN): TestResponse
    {
        $record = $this->factory(Authentication::class)
          ->roleOf($role)
          ->create()
          ->with('user')
          ->first();

        $authentication = $this->json(
            method: 'POST',
            uri: 'api/auth/login',
            data: [
            'identifier' => Uuid::uuid7()->toString(),
            'email' => $record->user->email,
            'password' => $record->user->password,
      ]
        );

        return $callback($authentication['accessToken']['value']);
    }

    /**
     * 墓地情報一覧取得APIを実行する.
     */
    private function hitListAPI(string|null $accessToken = null, array $conditions = []): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('api/cemeteries?%s', \http_build_query($conditions)),
            headers: !\is_null($accessToken) ? ['Authorization' => "Bearer {$accessToken}"] : []
        );
    }

    /**
     * 墓地情報取得APIを実行する.
     */
    private function hitFindAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('api/cemeteries/%s', $identifier),
            headers: !\is_null($accessToken) ? ['Authorization' => "Bearer {$accessToken}"] : []
        );
    }

    /**
     * 墓地情報作成APIを実行する.
     */
    private function hitCreateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: 'api/cemeteries',
            data: $payload,
            headers: !\is_null($accessToken) ? ['Authorization' => "Bearer {$accessToken}"] : []
        );
    }

    /**
     * 墓地情報更新APIを実行する.
     */
    private function hitUpdateAPI(string $identifier, array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('api/cemeteries/%s', $identifier),
            data: $payload,
            headers: !\is_null($accessToken) ? ['Authorization' => "Bearer {$accessToken}"] : []
        );
    }

    /**
     * 墓地情報削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('api/cemeteries/%s', $identifier),
            headers: !\is_null($accessToken) ? ['Authorization' => "Bearer {$accessToken}"] : []
        );
    }
}
