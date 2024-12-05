<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\Entities\User as Entity;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Http\Encoders\User\UserEncoder;
use App\Infrastructures\User\Models\User as Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
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
 * @group user
 *
 * @coversNothing
 *
 * @internal
 */
class UserControllerTest extends TestCase
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
    private UserEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(UserEncoder::class);
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
     * @testdox testAddSuccessReturnsSuccessfulResponse ユーザー追加APIに正しいリクエストを行ったとき正常なレスポンスが返却されること.
     *
     * @dataProvider provideRole
     */
    public function testAddSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            $role
        );

        $response->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddFailureReturnsUnauthorizedWithoutAuthentication ユーザー追加APIに未認証でリクエストを行ったとき401エラーが返却されること.
     */
    public function testAddFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->hitAddAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testAddReturnsConflictWithDuplicateIdentifier ユーザー追加APIに重複する識別子を指定してリクエストを行ったとき409エラーが返却されること.
     */
    public function testAddReturnsConflictWithDuplicateIdentifier(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
        );

        $response->assertConflict();
    }

    /**
     * @testdox testAddFailureReturnsConflictWithDuplicateEmail ユーザー追加APIに重複するメールアドレスを指定してリクエストを行ったとき409エラーが返却されること.
     */
    public function testAddFailureReturnsConflictWithDuplicateEmail(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'email' => $this->builder()->create(
                MailAddress::class,
                null,
                ['value' => $record->email]
            ),
        ]);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
        );

        $response->assertConflict();
    }

    /**
     * @testdox testUpdateSuccessReturnsSuccessfulResponse ユーザー更新APIに正しいリクエストを行ったとき正常なレスポンスが返却されること.
     */
    public function testUpdateSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
        );

        $response->assertNoContent();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateFailureReturnsUnauthorizedWithoutAuthentication ユーザー更新APIに未認証でリクエストを行ったとき401エラーが返却されること.
     */
    public function testUpdateFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->hitUpdateAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateFailureReturnsForbiddenWithInsufficientRole ユーザー更新APIに権限不足のリクエストを行ったとき403エラーが返却されること.
     */
    public function testUpdateFailureReturnsForbiddenWithInsufficientRole(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testUpdateFailureReturnsNotFoundWithInvalidIdentifier ユーザー更新APIに存在しない識別子を指定してリクエストを行ったとき404エラーが返却されること.
     */
    public function testUpdateFailureReturnsNotFoundWithInvalidIdentifier(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = $this->createPersistPayloadFromEntity($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse ユーザー取得APIに正しいリクエストを行ったとき正常なレスポンスが返却されること.
     */
    public function testFindSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $expected = $this->createFindExpectedResult($record);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($record->identifier, $accessToken),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testFindFailureReturnsNotFoundWithMissingIdentifier ユーザー取得APIに存在しない識別子を指定してリクエストを行ったとき404エラーが返却されること.
     */
    public function testFindFailureReturnsNotFoundWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken),
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindFailureReturnsUnauthorizedWithoutAuthentication ユーザー取得APIに未認証でリクエストを行ったとき401エラーが返却されること.
     */
    public function testFindFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $response = $this->hitFindAPI($record->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponse ユーザー一覧取得APIに正しいリクエストを行ったとき正常なレスポンスが返却されること.
     */
    public function testListSuccessReturnsSuccessfulResponse(): void
    {
        $expected = [
            'users' => $this->records->map(
                fn (Record $record): array => $this->createFindExpectedResult($record)
            )->all(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI([], $accessToken),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListFailureReturnsUnauthorizedWithoutAuthentication ユーザー一覧取得APIに未認証でリクエストを行ったとき401エラーが返却されること.
     */
    public function testListFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitListAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteSuccessReturnsSuccessfulResponse ユーザー削除APIに正しいリクエストを行ったとき正常なレスポンスが返却されること.
     */
    public function testDeleteSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('users', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteFailureReturnsUnauthorizedWithoutAuthentication ユーザー削除APIに未認証でリクエストを行ったとき401エラーが返却されること.
     */
    public function testDeleteFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $response = $this->hitDeleteAPI($record->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteFailureReturnsForbiddenWithInsufficientRole ユーザー削除APIに権限不足のリクエストを行ったとき403エラーが返却されること.
     */
    public function testDeleteFailureReturnsForbiddenWithInsufficientRole(): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * ユーザー追加APIを実行する.
     */
    private function hitAddAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: '/api/users',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * ユーザー更新APIを実行する.
     */
    private function hitUpdateAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('/api/users/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * ユーザー取得APIを実行する.
     */
    private function hitFindAPI(string $identifier, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/users/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * ユーザー一覧取得APIを実行する.
     */
    private function hitListAPI(array $conditions = [], ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/users?%s', \http_build_query($conditions)),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * ユーザー削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('/api/users/%s', $identifier),
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
        return [...$this->encoder->encode($entity), 'password' => 'Password!1'];
    }

    /**
     * 永続化した内容を検証する.
     */
    private function assertPersisted(array $payload): void
    {
        $this->assertDatabaseHas('users', [
            'identifier' => $payload['identifier'],
            'first_name' => $payload['name']['first'],
            'last_name' => $payload['name']['last'],
            'phone_number' => \json_encode($payload['phone']),
            'address' => \json_encode($payload['address']),
            'email' => $payload['email'],
            'role' => $payload['role'],
        ]);

        $record = Record::where('identifier', $payload['identifier'])->first();
        $this->assertTrue(Hash::check('Password!1', $record->password));
    }

    /**
     * ユーザー取得APIの期待結果を生成する.
     */
    private function createFindExpectedResult(Record $record): array
    {
        return [
            'identifier' => $record->identifier,
            'name' => [
                'first' => $record->first_name,
                'last' => $record->last_name,
            ],
            'address' => \json_decode($record->address, true),
            'phone' => \json_decode($record->phone_number, true),
            'email' => $record->email,
            'role' => $record->role,
        ];
    }
}
