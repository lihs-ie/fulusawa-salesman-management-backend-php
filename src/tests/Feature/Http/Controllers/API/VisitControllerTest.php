<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Http\Encoders\Visit\VisitEncoder;
use App\Infrastructures\Visit\Models\Visit as Record;
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
 * @group visit
 *
 * @coversNothing
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @internal
 */
class VisitControllerTest extends TestCase
{
    use DependencyBuildable;
    use FactoryResolvable;
    use RefreshDatabase;
    use WithAuthenticationCallable;

    /**
     * テストに使用するレコード.
     */
    private ?Enumerable $records;

    /**
     * テストに使用するエンコーダ.
     */
    private VisitEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(VisitEncoder::class);
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
     * @testdox testAddSuccessReturnsSuccessfulResponse 訪問追加APIに正しいリクエストを行ったとき正常なレスポンスが返ること.
     */
    public function testAddSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(
                Entity::class,
                null,
                ['user' => $this->builder()->create(UserIdentifier::class, null, ['value' => $record->user])]
            )
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddFailureReturnsUnauthorizedWithoutAuthentication 訪問追加APIに未認証でリクエストを行ったとき401エラーが返ること.
     */
    public function testAddFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitAddAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testAddFailureReturnsForbiddenWithInvalidRole 訪問追加APIに権限のないユーザーでリクエストを行ったとき403エラーが返ること.
     */
    public function testAddFailureReturnsForbiddenWithInvalidRole(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI([], $accessToken),
            Role::ADMIN
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testAddFailureReturnsConflictWithDuplicatedIdentifier 訪問追加APIに重複した識別子でリクエストを行ったとき409エラーが返ること.
     */
    public function testAddFailureReturnsConflictWithDuplicatedIdentifier(): void
    {
        $record = $this->records->random();

        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(
                class: Entity::class,
                overrides: [
                    'identifier' => $this->builder()->create(VisitIdentifier::class, null, ['value' => $record->identifier]),
                    'user' => $this->builder()->create(UserIdentifier::class, null, ['value' => $record->user]),
                ]
            )
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertConflict();
    }

    /**
     * @testdox testAddFailureReturnsBadRequestWithMissingUser 訪問追加APIに存在しないユーザーを指定してリクエストを行ったとき400エラーが返ること.
     */
    public function testAddFailureReturnsBadRequestWithMissingUser(): void
    {
        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(Entity::class)
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertBadRequest();
    }

    /**
     * @testdox testUpdateSuccessReturnsSuccessfulResponse 訪問更新APIに正しいリクエストを行ったとき正常なレスポンスが返ること.
     */
    public function testUpdateSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(
                Entity::class,
                null,
                [
                    'identifier' => $this->builder()->create(VisitIdentifier::class, null, ['value' => $record->identifier]),
                    'user' => $this->builder()->create(UserIdentifier::class, null, ['value' => $record->user]),
                ]
            )
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertNoContent();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateFailureReturnsUnauthorizedWithoutAuthentication 訪問更新APIに未認証でリクエストを行ったとき401エラーが返ること.
     */
    public function testUpdateFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(
                Entity::class,
                null,
                [
                    'identifier' => $this->builder()->create(VisitIdentifier::class, null, ['value' => $record->identifier]),
                    'user' => $this->builder()->create(UserIdentifier::class, null, ['value' => $record->user]),
                ]
            )
        );

        $response = $this->hitUpdateAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateFailureReturnsForbiddenWithInvalidRole 訪問更新APIに権限のないユーザーでリクエストを行ったとき403エラーが返ること.
     */
    public function testUpdateFailureReturnsForbiddenWithInvalidRole(): void
    {
        $record = $this->records->random();

        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(
                Entity::class,
                null,
                [
                    'identifier' => $this->builder()->create(VisitIdentifier::class, null, ['value' => $record->identifier]),
                    'user' => $this->builder()->create(UserIdentifier::class, null, ['value' => $record->user]),
                ]
            )
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::ADMIN
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testUpdateFailureReturnsNotFoundWithMissingIdentifier 訪問更新APIに存在しない識別子でリクエストを行ったとき404エラーが返ること.
     */
    public function testUpdateFailureReturnsNotFoundWithMissingIdentifier(): void
    {
        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(Entity::class)
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testUpdateFailureReturnsBadRequestWithMissingUser 訪問更新APIに存在しないユーザーを指定してリクエストを行ったとき400エラーが返ること.
     */
    public function testUpdateFailureReturnsBadRequestWithMissingUser(): void
    {
        $record = $this->records->random();

        $payload = $this->createPersistPayloadFromEntity(
            $this->builder()->create(
                Entity::class,
                null,
                ['identifier' => $this->builder()->create(VisitIdentifier::class, null, ['value' => $record->identifier])]
            )
        );

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertBadRequest();
    }

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse 訪問取得APIに正しいリクエストを行ったとき正常なレスポンスが返ること.
     *
     * @dataProvider provideRole
     */
    public function testFindSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($record->identifier, $accessToken),
            $role
        );

        $response->assertSuccessful();
        $response->assertJson($this->createFindExpectedResult($record));
    }

    /**
     * @testdox testFindFailureReturnsUnauthorizedWithoutAuthentication 訪問取得APIに未認証でリクエストを行ったとき401エラーが返ること.
     */
    public function testFindFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $response = $this->hitFindAPI($record->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testFindFailureReturnsNotFoundWithMissingIdentifier 訪問取得APIに存在しない識別子でリクエストを行ったとき404エラーが返ること.
     *
     * @dataProvider provideRole
     */
    public function testFindFailureReturnsNotFoundWithMissingIdentifier(Role $role): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken),
            $role
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponse 訪問一覧取得APIに正しいリクエストを行ったとき正常なレスポンスが返ること.
     *
     * @dataProvider provideRole
     */
    public function testListSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $expected = [
            'visits' => $this->records
                ->map(fn (Record $record): array => $this->createFindExpectedResult($record))
                ->all(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI([], $accessToken),
            $role
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListFailureReturnsUnauthorizedWithoutAuthentication 訪問一覧取得APIに未認証でリクエストを行ったとき401エラーが返ること.
     */
    public function testListFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitListAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteSuccessReturnsSuccessfulResponse 訪問削除APIに正しいリクエストを行ったとき正常なレスポンスが返るこ.
     */
    public function testDeleteSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::USER
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('visits', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteFailureReturnsUnauthorizedWithoutAuthentication 訪問削除APIに未認証でリクエストを行ったとき401エラーが返ること.
     */
    public function testDeleteFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $response = $this->hitDeleteAPI($record->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteFailureReturnsForbiddenWithInvalidRole 訪問削除APIに権限のないユーザーでリクエストを行ったとき403エラーが返ること.
     */
    public function testDeleteFailureReturnsForbiddenWithInvalidRole(): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::ADMIN
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testDeleteFailureReturnsNotFoundWithMissingIdentifier 訪問削除APIに存在しない識別子でリクエストを行ったとき404エラーが返ること.
     */
    public function testDeleteFailureReturnsNotFoundWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(Uuid::uuid7()->toString(), $accessToken),
            Role::USER
        );

        $response->assertNotFound();
    }

    /**
     * 訪問追加APIを実行する.
     */
    private function hitAddAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: '/api/visits',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 訪問更新APIを実行する.
     */
    private function hitUpdateAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('/api/visits/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 訪問取得APIを実行する.
     */
    private function hitFindAPI(string $identifier, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/visits/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 訪問一覧取得APIを実行する.
     */
    private function hitListAPI(array $conditions = [], ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/visits?%s', \http_build_query($conditions)),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 訪問削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('/api/visits/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * テストに使用するレコードを生成する.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * 永続化系APIのペイロードを生成する.
     */
    private function createPersistPayloadFromEntity(Entity $entity): array
    {
        return $this->encoder->encode($entity);
    }

    /**
     * 永続化した内容を検証する.
     */
    private function assertPersisted(array $payload): void
    {
        $phone = $payload['phone'];

        $this->assertDatabaseHas('visits', [
            'identifier' => $payload['identifier'],
            'user' => $payload['user'],
            'visited_at' => $payload['visitedAt'],
            'phone_number' => \is_null($phone) ? null : json_encode($phone),
            'address' => \json_encode($payload['address']),
            'has_graveyard' => $payload['hasGraveyard'],
            'result' => $payload['result'],
        ]);
    }

    /**
     * 訪問取得APIの期待結果を生成する.
     */
    private function createFindExpectedResult(Record $record): array
    {
        return [
            'identifier' => $record->identifier,
            'user' => $record->user,
            'visitedAt' => $record->visited_at->format(\DATE_ATOM),
            'phone' => !\is_null($record->phone_number) ? json_decode($record->phone_number, true) : null,
            'address' => json_decode($record->address, true),
            'hasGraveyard' => $record->has_graveyard,
            'note' => $record->note,
            'result' => $record->result,
        ];
    }
}
