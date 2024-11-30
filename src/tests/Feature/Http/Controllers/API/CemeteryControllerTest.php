<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\User\ValueObjects\Role;
use App\Http\Encoders\Cemetery\CemeteryEncoder;
use App\Infrastructures\Authentication\Models\Authentication;
use App\Infrastructures\Cemetery\Models\Cemetery as Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\WithAuthenticationCallable;
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
    use WithAuthenticationCallable;

    /**
     * テストに使用するレコード.
     */
    private Enumerable|null $records;

    /**
     * テストに使用するエンコーダ.
     */
    private CemeteryEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(CemeteryEncoder::class);
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
     * @testdox testAddReturnsSuccessfulResponse 墓地情報追加APIで正常なリクエストが送信されたとき正常なレスポンスが返却されること.
     * @dataProvider provideRole
     */
    public function testAddReturnsSuccessfulResponse(Role $role): void
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

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            $role
        );

        $response->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @textdox testAddReturnsBadRequestWithMissingCustomer 墓地情報追加APIで存在しない顧客が指定されたときBadRequestが返却されること.
     * @dataProvider provideRole
     */
    public function testAddReturnsBadRequestWithMissingCustomer(Role $role): void
    {
        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => 'name',
            'type' => CemeteryType::INDIVIDUAL->name,
            'construction' => '2021-01-01T00:00:00+00:00',
            'inHouse' => true,
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            $role
        );

        $response->assertBadRequest();
    }

    /**
     * @testdox testAddReturnsUnauthorized 墓地情報作成APIで未認証のリクエストがされたときUnAuthorizedが返却されること.
     */
    public function testAddReturnsUnauthorized(): void
    {
        $response = $this->hitAddAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateReturnsSuccessfulResponse 墓地情報更新APIで正常なリクエストが実行されたとき正常なレスポンスが返却されること.
     * @dataProvider provideRole
     */
    public function testUpdateReturnsSuccessfulResponse(Role $role): void
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

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($record->identifier, $payload, $accessToken),
            $role
        );

        $response->assertNoContent();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateReturnsBadRequestWithMissingCustomer 墓地情報更新APIで存在しない顧客が指定されたときBadRequestが返却されること.
     * @dataProvider provideRole
     */
    public function testUpdateReturnsBadRequestWithMissingCustomer(Role $role): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                CemeteryIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => $entity->name(),
            'type' => $entity->type()->name,
            'construction' => $entity->construction()->toAtomString(),
            'inHouse' => $entity->inHouse(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($record->identifier, $payload, $accessToken),
            $role
        );

        $response->assertBadRequest();
    }

    /**
     * @testdox testUpdateReturnsUnAuthorizedWithoutLogin 墓地情報更新APIで未認証のときUnAuthorizedが返却されること.
     */
    public function testUpdateReturnsUnAuthorizedWithoutLogin(): void
    {
        $response = $this->hitUpdateAPI(Uuid::uuid7()->toString(), []);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testReturnsSuccessfulResponse 墓地情報一覧取得APIで正常なリクエストが送信されたとき正常なレスポンスが返却されること.
     * @dataProvider provideConditions
     */
    public function testListReturnsSuccessfulResponse(array $conditions): void
    {
        $expected = $this->createListExpectedResult($this->records, $conditions);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI($accessToken, $conditions),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * 検索条件を提供するプロパイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [[]];

        yield 'customer' => [['customer' => Uuid::uuid7()->toString()]];
    }

    /**
     * @testdox testListReturnsUnAuthorizedWithOutLogin 墓地情報一覧取得APIで未認証のときUnAuthorizedが返却されること.
     */
    public function testListReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitListAPI();

        $response->assertUnauthorized();
    }

    /**
     * @testdox testFindReturnsSuccessfulResponse 墓地情報取得APIで正常なリクエストが送信されたとき正常なレスポンスが返却されること.
     */
    public function testFindReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord();

        $expected = $this->createFindExpectedResult($record);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($record->identifier, $accessToken)
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }


    /**
     * @testdox testFindReturnsNotFoundWithNotExistsIdentifier 墓地情報取得APIで存在しない識別子が指定されたときNotFoundが返却されること.
     */
    public function testFindReturnsNotFoundWithNotExistsIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindReturnsUnAuthorizedWithOutLogin 墓地情報取得APIで未認証のときUnAuthorizedが返却されること.
     */
    public function testFindReturnsUnAuthorizedWithOutLogin(): void
    {
        $response = $this->hitFindAPI(Uuid::uuid7()->toString());

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteReturnsSuccessfulResponse 墓地情報削除APIで正常なリクエストが送信されたとき正常なレスポンスが返却されること.
     */
    public function testDeleteReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken)
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('cemeteries', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteReturnsNotFoundWithMissingIdentifier 墓地情報削除APIで存在しない識別子が指定されたときNotFoundが返却されること.
     */
    public function testDeleteReturnsNotFoundWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(Uuid::uuid7()->toString(), $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testDeleteReturnsUnAuthorizedWithAdminUser 墓地情報削除APIでユーザー権限でリクエストしたときUnAuthorizedが返却されること.
     */
    public function testDeleteReturnsUnAuthorizedWithNormalUser(): void
    {
        $record = $this->pickRecord();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testDeleteReturnsUnAuthorizedWithoutLogin 墓地情報削除APIで未認証のときUnAuthorizedが返却されること.
     */
    public function testDeleteReturnsUnAuthorizedWithoutLogin(): void
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
     * 墓地情報追加APIを実行する.
     */
    private function hitAddAPI(array $payload, string|null $accessToken = null): TestResponse
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

    /**
     * 永続化した内容を検証する.
     */
    private function assertPersisted(array $payload): void
    {
        $this->assertDatabaseHas('cemeteries', [
            'identifier' => $payload['identifier'],
            'customer' => $payload['customer'],
            'name' => $payload['name'],
            'type' => $payload['type'],
            'construction' => $payload['construction'],
            'in_house' => $payload['inHouse'],
        ]);
    }

    /**
     * 墓地情報一覧取得APIの期待結果を生成する.
     */
    private function createListExpectedResult(Enumerable $records, array $conditions): array
    {
        return [
            'cemeteries' => $records
                ->when(isset($conditions['customer']), fn (Enumerable $records) => $records->filter(
                    fn (Record $record): bool => $record->customer === $conditions['customer']
                ))
                ->map(fn (Record $record): array => $this->createFindExpectedResult($record))
                ->all()
        ];
    }

    /**
     * 墓地情報取得APIの期待結果を生成する.
     */
    private function createFindExpectedResult(Record $record): array
    {
        return [
            'identifier' => $record->identifier,
            'customer' => $record->customer,
            'name' => $record->name,
            'type' => $record->type,
            'construction' => $record->construction->format(\DATE_ATOM),
            'inHouse' => $record->in_house,
        ];
    }
}
