<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\User\ValueObjects\Role;
use App\Http\Encoders\Customer\CustomerEncoder;
use App\Infrastructures\Customer\Models\Customer as Record;
use Closure;
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
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
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
     * @testdox testAddSuccessReturnsSuccessfulResponse 顧客追加APIを正常なリクエストで実行したとき正常なレスポンスが返却されること.
     */
    public function testAddSuccessReturnsSuccessfulResponse(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->encoder->encode($entity);

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI(
                accessToken: $accessToken,
                payload: $payload
            )
        );

        $actual->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddReturnsConflictWithDuplicateIdentifier 顧客追加APIを重複する識別子で実行したときConflictが返却されること.
     */
    public function testAddReturnsConflictWithDuplicateIdentifier(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $this->builder()->create(
                CustomerIdentifier::class,
                null,
                ['value' => $record->identifier]
            )]
        );

        $payload = $this->encoder->encode($entity);

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI(
                accessToken: $accessToken,
                payload: $payload
            )
        );

        $actual->assertConflict();
    }

    /**
     * @testdox testAddReturnsUnauthorizedWithoutAccessToken 顧客追加APIを未認証で実行したときUnauthorizedが返却されること.
     */
    public function testAddReturnsUnauthorizedWithoutAccessToken(): void
    {
        $actual = $this->hitAddAPI(
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
            ['identifier' => $this->builder()->create(
                CustomerIdentifier::class,
                null,
                ['value' => $record->identifier]
            )]
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

    /**
     * @testdox testUpdateReturnsNotFoundWithMissingIdentifier 顧客更新APIを存在しない識別子で実行したときNotFoundが返却されること.
     */
    public function testUpdateReturnsNotFoundWithMissingIdentifier(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI(
                accessToken: $accessToken,
                payload: $this->encoder->encode($entity)
            )
        );

        $actual->assertNotFound();
    }

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

        $expected = $this->createExpectedFromRecord($target);

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
     * @dataProvider provideConditions
     */
    public function testListSuccessReturnsSuccessfulResponse(Closure $closure): void
    {
        $record = $this->records->random();

        $conditions = $closure($record);

        $expected = $this->createExpectedListFromRecords($conditions);

        $actual = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI(accessToken: $accessToken, conditions: $conditions)
        );

        $actual->assertSuccessful();
        $actual->assertJson($expected);
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [fn (): array => []];

        yield 'first name' => [fn (Record $record): array => ['name' => $record->first_name]];

        yield 'last name' => [fn (Record $record): array => ['name' => $record->last_name]];

        yield 'phone' => [fn (Record $record): array => ['phone' => [
            'areaCode' => $record->phone_area_code,
            'localCode' => $record->phone_local_code,
            'subscriberNumber' => $record->phone_subscriber_number,
        ]]];

        yield 'postal code' => [fn (Record $record): array => ['postalCode' => [
            'first' => $record->postal_code_first,
            'second' => $record->postal_code_second,
        ]]];
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
     * 顧客追加APIを実行する.
     */
    private function hitAddAPI(array $payload, string|null $accessToken = null): TestResponse
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
    private function hitListAPI(array $conditions = [], string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/customers?%s', \http_build_query($conditions)),
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

    /**
     * 一覧取得APIの期待値を生成する.
     */
    private function createExpectedListFromRecords(array $conditions): array
    {
        return [
            'customers' => $this->records
                ->when(
                    isset($conditions['name']),
                    function (Enumerable $records) use ($conditions): Enumerable {
                        $name = $conditions['name'];

                        return $records->filter(fn (Record $record): bool => \str_contains($record->first_name, $name) || \str_contains($record->last_name, $name));
                    }
                )
                ->when(
                    isset($conditions['phone']),
                    fn (Enumerable $records): Enumerable => $records
                        ->where('phone_area_code', $conditions['phone']['areaCode'])
                        ->where('phone_local_code', $conditions['phone']['localCode'])
                        ->where('phone_subscriber_number', $conditions['phone']['subscriberNumber'])
                )
                ->when(
                    isset($conditions['postalCode']),
                    fn (Enumerable $records) => $records
                        ->where('postal_code_first', $conditions['postalCode']['first'])
                        ->where('postal_code_second', $conditions['postalCode']['second'])
                )
                ->map(fn (Record $record): array => $this->createExpectedFromRecord($record))
                ->values()
                ->all()
        ];
    }
}
