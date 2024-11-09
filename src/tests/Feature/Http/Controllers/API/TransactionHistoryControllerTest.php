<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\User\ValueObjects\Role;
use App\Http\Encoders\TransactionHistory\TransactionHistoryEncoder;
use App\Infrastructures\TransactionHistory\Models\TransactionHistory as Record;
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
 * @group controllers
 * @group api
 * @group transactionhistory
 *
 * @coversNothing
 */
class TransactionHistoryControllerTest extends TestCase
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
    private TransactionHistoryEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(TransactionHistoryEncoder::class);
        $this->records = $this->createRecords();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $this->records = null;

        parent::tearDown();
    }

    /**
     * @testdox testAddSuccessReturnsSuccessfulResponse 取引履歴追加APIで取引履歴を追加できること.
     */
    public function testAddSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'customer' => $record->customer,
          'type' => $record->type,
          'description' => $record->description,
          'date' => $record->date,
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken)
        );

        $response->assertSuccessful();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddFailureReturnsUnauthorizedResponse 取引履歴追加APIで未認証の場合は401エラーを返すこと.
     */
    public function testAddFailureReturnsUnauthorizedResponse(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'customer' => $record->customer,
          'type' => $record->type,
          'description' => $record->description,
          'date' => $record->date,
        ];

        $response = $this->hitAddAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateSuccessReturnsSuccessfulResponse 取引履歴更新APIで取引履歴を更新できること.
     */
    public function testUpdateSuccessReturnsSuccessfulResponse(): void
    {
        $target = $this->records->random();

        $payload = [
          'identifier' => $target->identifier,
          'user' => $target->user,
          'customer' => $target->customer,
          'type' => $target->type,
          'description' => $target->description,
          'date' => $target->date,
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken)
        );

        $response->assertSuccessful();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateFailureReturnsNotFoundResponse 取引履歴更新APIで存在しない取引履歴を更新しようとした場合404エラーを返すこと.
     */
    public function testUpdateFailureReturnsNotFoundResponse(): void
    {
        $this->markTestSkipped('persistをaddとupdateを分けるように修正の後に実装');
    }

    /**
     * @testdox testUpdateFailureReturnsForbiddenResponse 取引履歴更新APIにユーザー権限でリクエストしたとき403エラーを返すこと.
     */
    public function testUpdateFailureReturnsForbiddenResponse(): void
    {
        $target = $this->records->random();

        $payload = [
          'identifier' => $target->identifier,
          'user' => $target->user,
          'customer' => $target->customer,
          'type' => $target->type,
          'description' => $target->description,
          'date' => $target->date,
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testUpdateFailureReturnsUnauthorizedResponse 取引履歴更新APIで未認証の場合は401エラーを返すこと.
     */
    public function testUpdateFailureReturnsUnauthorizedResponse(): void
    {
        $target = $this->records->random();

        $payload = [
          'identifier' => $target->identifier,
          'user' => $target->user,
          'customer' => $target->customer,
          'type' => $target->type,
          'description' => $target->description,
          'date' => $target->date,
        ];

        $response = $this->hitUpdateAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse 取引履歴取得APIで取引履歴を取得できること.
     * @dataProvider provideRole
     */
    public function testFindSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $target = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($target->identifier, $accessToken),
            $role
        );

        $response->assertSuccessful();
        $response->assertJson($this->createFindExpectedResult($target));
    }

    /**
     * @testdox testFindFailureReturnsNotFoundResponseWithMissingIdentifier 取引履歴取得APIで存在しない取引履歴を取得しようとした場合404エラーを返すこと.
     */
    public function testFindFailureReturnsNotFoundResponseWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindFailureReturnsUnauthorizedResponse 取引履歴取得APIで未認証の場合は401エラーを返すこと.
     */
    public function testFindFailureReturnsUnauthorizedResponse(): void
    {
        $target = $this->records->random();

        $response = $this->hitFindAPI($target->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponse 取引履歴一覧取得APIで取引履歴一覧を取得できること.
     * @dataProvider provideRole
     */
    public function testListSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI([], $accessToken),
            $role
        );

        $expected = [
          'transactionHistories' => $this->records
            ->map(fn (Record $record): array => $this->createFindExpectedResult($record))
            ->all()
        ];

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListFailureReturnsUnauthorizedResponse 取引履歴一覧取得APIで未認証の場合は401エラーを返すこと.
     */
    public function testListFailureReturnsUnauthorizedResponse(): void
    {
        $response = $this->hitListAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteSuccessReturnsSuccessfulResponse 取引履歴削除APIで取引履歴を削除できること.
     */
    public function testDeleteSuccessReturnsSuccessfulResponse(): void
    {
        $target = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($target->identifier, $accessToken)
        );

        $response->assertSuccessful();
        $response->assertNoContent();
        $this->assertDatabaseMissing('transaction_histories', ['identifier' => $target->identifier]);
    }

    /**
     * @testdox testDeleteFailureReturnsNotFoundResponseWithMissingIdentifier 取引履歴削除APIで存在しない取引履歴を削除しようとした場合404エラーを返すこと.
     */
    public function testDeleteFailureReturnsNotFoundResponseWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(Uuid::uuid7()->toString(), $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testDeleteFailureReturnsUnauthorizedResponse 取引履歴削除APIで未認証の場合は401エラーを返すこと.
     */
    public function testDeleteFailureReturnsUnauthorizedResponse(): void
    {
        $target = $this->records->random();

        $response = $this->hitDeleteAPI($target->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteFailureReturnsForbiddenResponse 取引履歴削除APIにユーザー権限でリクエストしたとき403エラーを返すこと.
     */
    public function testDeleteFailureReturnsForbiddenResponse(): void
    {
        $target = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($target->identifier, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * テストに使用するレコードを生成する.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * 取引履歴追加APIを実行する.
     */
    private function hitAddAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: '/api/transaction-histories',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 取引履歴更新APIを実行する.
     */
    private function hitUpdateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('/api/transaction-histories/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 取引履歴取得APIを実行する.
     */

    private function hitFindAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/transaction-histories/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 取引履歴一覧取得APIを実行する.
     */
    private function hitListAPI(array $conditions = [], string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/transaction-histories?%s', \http_build_query($conditions)),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 取引履歴削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('/api/transaction-histories/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 永続化した内容を検証する.
     */
    private function assertPersisted(array $payload): void
    {
        $this->assertDatabaseHas('transaction_histories', [
          'identifier' => $payload['identifier'],
          'user' => $payload['user'],
          'customer' => $payload['customer'],
          'type' => $payload['type'],
          'description' => $payload['description'],
          'date' => $payload['date'],
        ]);
    }

    /**
     * 取引履歴取得APIの期待結果を生成する.
     */
    private function createFindExpectedResult(Record $record): array
    {
        return [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'customer' => $record->customer,
          'type' => $record->type,
          'description' => $record->description,
          'date' => $record->date,
        ];
    }
}
