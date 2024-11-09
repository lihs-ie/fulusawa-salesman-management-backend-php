<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\User\ValueObjects\Role;
use App\Http\Encoders\Customer\CustomerEncoder;
use App\Infrastructures\Customer\Models\Customer as Record;
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
 * @group customer
 *
 * @coversNothing
 */
class CustomerControllerTest extends TestCase
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
     * テストに使用する顧客エンコーダ.
     */
    private CustomerEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(CustomerEncoder::class);
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
     * @testdox testCreateSuccessReturnsSuccessfulResponse 顧客作成APIを正常なリクエストで実行したとき正常なレスポンスが返却されること.
     */
    public function testCreateSuccessReturnsSuccessfulResponse(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($entity);

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI(
                accessToken: $accessToken,
                payload: $payload
            )
        );

        $actual->assertSuccessful();
        $actual->assertStatus(201);

        $this->assertPersisted($payload);
    }

    /**
     * @testdox testCreateReturnsUnauthorizedWithoutAccessToken 顧客作成APIを未認証で実行したときUnauthorizedが返却されること.
     */
    public function testCreateReturnsUnauthorizedWithoutAccessToken(): void
    {
        $actual = $this->hitCreateAPI(
            $this->encoder->encode($this->builder()->create(Entity::class))
        );

        $actual->assertUnauthorized();
    }

    /**
     * @testdox testUpdateSuccessReturnsSuccessfulResponse 顧客更新APIを正常なリクエストで実行したとき正常なレスポンスが返却されること.
     */
    public function testUpdateSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $record->identifier]
        );

        $payload = $this->encoder->encode($entity);

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI(
                accessToken: $accessToken,
                payload: $payload
            )
        );

        $actual->assertSuccessful();
        $actual->assertStatus(204);
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateReturnsUnauthorizedWithoutAccessToken 顧客更新APIを未認証で実行したときUnauthorizedが返却されること.
     */
    public function testUpdateReturnsUnauthorizedWithoutAccessToken(): void
    {
        $actual = $this->hitUpdateAPI(
            $this->encoder->encode($this->builder()->create(Entity::class))
        );

        $actual->assertUnauthorized();
    }

    // TODO: create, updateをpersistメソッドを使い分け後実装する

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse 顧客取得APIを正常なリクエストで実行したとき正常なレスポンスが返却されること.
     */
    public function testFindSuccessReturnsSuccessfulResponse(): void
    {
        $target = $this->records->random();

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(
                accessToken: $accessToken,
                identifier: $target->identifier
            )
        );

        $expected = ['customer' => $this->createExpectedFromRecord($target)];

        $actual->assertSuccessful();
        $actual->assertJson($expected);
    }

    /**
     * @testdox testFindReturnsNotFoundWithMissingIdentifier 顧客取得APIを存在しない識別子で実行したときNotFoundが返却されること.
     */
    public function testFindReturnsNotFoundWithMissingIdentifier(): void
    {
        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(
                accessToken: $accessToken,
                identifier: Uuid::uuid7()->toString()
            )
        );

        $actual->assertNotFound();
    }

    /**
     * @testdox testFindReturnsUnauthorizedWithoutAccessToken 顧客取得APIを未認証で実行したときUnauthorizedが返却されること.
     */
    public function testFindReturnsUnauthorizedWithoutAccessToken(): void
    {
        $actual = $this->hitFindAPI($this->records->random()->identifier);

        $actual->assertUnauthorized();
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponse 顧客一覧取得APIを正常なリクエストで実行したとき正常なレスポンスが返却されること.
     */
    public function testListSuccessReturnsSuccessfulResponse(): void
    {
        $expected = [
          'customers' => $this->records->map(
              fn (Record $record): array => $this->createExpectedFromRecord($record)
          )->all()
        ];

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI(accessToken: $accessToken)
        );

        $actual->assertSuccessful();
        $actual->assertJson($expected);
    }

    /**
     * @testdox testListReturnsUnauthorizedWithoutAccessToken 顧客一覧取得APIを未認証で実行したときUnauthorizedが返却されること.
     */
    public function testListReturnsUnauthorizedWithoutAccessToken(): void
    {
        $actual = $this->hitListAPI();

        $actual->assertUnauthorized();
    }

    /**
     * @testdox testDeleteSuccessReturnsSuccessfulResponse 顧客削除APIを正常なリクエストで実行したとき正常なレスポンスが返却されること.
     */
    public function testDeleteSuccessReturnsSuccessfulResponse(): void
    {
        $target = $this->records->random();

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(
                accessToken: $accessToken,
                identifier: $target->identifier
            )
        );

        $actual->assertSuccessful();
        $actual->assertStatus(204);
    }

    /**
     * @testdox testDeleteReturnsNotFoundWithMissingIdentifier 顧客削除APIを存在しない識別子で実行したときNotFoundが返却されること.
     */
    public function testDeleteReturnsNotFoundWithMissingIdentifier(): void
    {
        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(
                accessToken: $accessToken,
                identifier: Uuid::uuid7()->toString()
            ),
        );

        $actual->assertNotFound();
    }

    /**
     * @testdox testDeleteReturnsUnauthorizedWithoutAccessToken 顧客削除APIを未認証で実行したときUnauthorizedが返却されること.
     */
    public function testDeleteReturnsUnauthorizedWithoutAccessToken(): void
    {
        $actual = $this->hitDeleteAPI($this->records->random()->identifier);

        $actual->assertUnauthorized();
    }

    /**
     * @testdox testDeleteReturnsForbiddenWithUserRole 顧客削除APIをユーザー権限で実行したときForbiddenが返却されること.
     */
    public function testDeleteReturnsForbiddenWithUserRole(): void
    {
        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(
                accessToken: $accessToken,
                identifier: $this->records->random()->identifier
            ),
            Role::USER
        );

        $actual->assertForbidden();
    }

    /**
     * テストに使用するレコードを生成するへルパ.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)
          ->createMany(\mt_rand(5, 10));
    }

    /**
     * 顧客作成APIを実行する.
     */
    private function hitCreateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: '/api/customers',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 顧客更新APIを実行する.
     */
    private function hitUpdateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('/api/customers/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 顧客取得APIを実行する.
     */
    private function hitFindAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/customers/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 顧客一覧取得APIを実行する.
     */
    private function hitListAPI(string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: '/api/customers',
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 顧客削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('/api/customers/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 永続化内容を比較する.
     */
    private function assertPersisted(array $expected): void
    {
        $this->assertDatabaseHas('customers', [
          'identifier' => $expected['identifier'],
          'last_name' => $expected['name']['last'],
          'first_name' => $expected['name']['first'],
          'phone_area_code' => $expected['phone']['areaCode'],
          'phone_local_code' => $expected['phone']['localCode'],
          'phone_subscriber_number' => $expected['phone']['subscriberNumber'],
          'postal_code_first' => $expected['address']['postalCode']['first'],
          'postal_code_second' => $expected['address']['postalCode']['second'],
          'prefecture' => $expected['address']['prefecture'],
          'city' => $expected['address']['city'],
          'street' => $expected['address']['street'],
          'building' => $expected['address']['building'],
          'cemeteries' => \json_encode($expected['cemeteries']),
          'transaction_histories' => \json_encode($expected['transactionHistories']),
        ]);
    }

    /**
     * レコードから期待値を生成する.
     */
    private function createExpectedFromRecord(Record $record): array
    {
        return [
          'identifier' => $record->identifier,
          'name' => [
            'last' => $record->last_name,
            'first' => $record->first_name,
          ],
          'phone' => [
            'areaCode' => $record->phone_area_code,
            'localCode' => $record->phone_local_code,
            'subscriberNumber' => $record->phone_subscriber_number,
          ],
          'address' => [
            'postalCode' => [
              'first' => $record->postal_code_first,
              'second' => $record->postal_code_second,
            ],
            'prefecture' => $record->prefecture,
            'city' => $record->city,
            'street' => $record->street,
            'building' => $record->building,
          ],
          'cemeteries' => \json_decode($record->cemeteries, true),
          'transactionHistories' => \json_decode($record->transaction_histories, true),
        ];
    }
}
