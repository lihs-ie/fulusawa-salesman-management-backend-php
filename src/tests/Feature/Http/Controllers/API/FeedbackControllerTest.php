<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\Domains\User\ValueObjects\Role;
use App\Infrastructures\Feedback\Models\Feedback as Record;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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
 * @group feedback
 *
 * @coversNothing
 */
class FeedbackControllerTest extends TestCase
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
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

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
     * @testdox testAddSuccessReturnsSuccessfulResponse フィードバック追加APIを実行すると成功レスポンスが返却されること.
     */
    public function testAddSuccessReturnsSuccessfulResponse(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddFailureReturnsForbiddenWithAdminRole フィードバック追加APIを管理者権限で実行すると403エラーが返却されること.
     */
    public function testAddFailureReturnsForbiddenWithAdminRole(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI($payload, $accessToken),
            Role::ADMIN
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testAddFailureReturnsUnauthorizedWithoutAuthentication フィードバック追加APIを未認証で実行すると401エラーが返却されること.
     */
    public function testAddFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->hitCreateAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testAddFailureReturnsConflictWithDuplicateIdentifier フィードバック追加APIを重複識別子で実行すると409エラーが返却されること.
     */
    public function testAddFailureReturnsConflictWithDuplicateIdentifier(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                FeedbackIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitCreateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertConflict();
    }

    /**
     * @testdox testUpdateSuccessReturnsSuccessfulResponse フィードバック更新APIを実行すると成功レスポンスが返却されること.
     */
    public function testUpdateSuccessReturnsSuccessfulResponse(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                FeedbackIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => CarbonImmutable::parse($record->created_at),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::ADMIN
        );

        $response->assertNoContent();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateFailureReturnsForbiddenWithUserRole フィードバック更新APIをユーザー権限で実行すると403エラーが返却されること.
     */
    public function testUpdateFailureReturnsForbiddenWithUserRole(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                FeedbackIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = [
            'identifier' => $record->identifier,
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testUpdateFailureReturnsUnauthorizedWithoutAuthentication フィードバック更新APIを未認証で実行すると401エラーが返却されること.
     */
    public function testUpdateFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                FeedbackIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $payload = [
            'identifier' => $record->identifier,
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->hitUpdateAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateFailureReturnsNotFoundWithMissingIdentifier フィードバック更新APIを存在しない識別子で実行すると404エラーが返却されること.
     */
    public function testUpdateFailureReturnsNotFoundWithMissingIdentifier(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $payload = [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::ADMIN
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse 正しい値でフィードバック取得APIを実行すると成功レスポンスが返却されること.
     *
     * @dataProvider provideUserRole
     */
    public function testFindSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $target = $this->records->random();

        $expected = [
            'identifier' => $target->identifier,
            'type' => $target->type,
            'status' => $target->status,
            'content' => $target->content,
            'createdAt' => $target->created_at->format(\DATE_ATOM),
            'updatedAt' => $target->updated_at->format(\DATE_ATOM),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($target->identifier, $accessToken),
            $role
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testFindFailureReturnsNotFoundWithMissingIdentifier フィードバック取得APIを存在しない識別子で実行すると404エラーが返却されること.
     */
    public function testFindFailureReturnsNotFoundWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken),
            Role::USER
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindFailureReturnsUnauthorizedWithoutAuthentication フィードバック取得APIを未認証で実行すると401エラーが返却されること.
     */
    public function testFindFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $target = $this->records->random();

        $response = $this->hitFindAPI($target->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponse フィードバック一覧取得APIを実行すると成功レスポンスが返却されること.
     *
     * @dataProvider provideConditions
     */
    public function testListSuccessReturnsSuccessfulResponse(array $conditions): void
    {
        $sortBy = fn (Enumerable $records, string $sort): Enumerable => match ($sort) {
            Sort::CREATED_AT_ASC->name => $records->sortBy('created_at'),
            Sort::CREATED_AT_DESC->name => $records->sortByDesc('created_at'),
            Sort::UPDATED_AT_ASC->name => $records->sortBy('updated_at'),
            Sort::UPDATED_AT_DESC->name => $records->sortByDesc('updated_at'),
        };

        $expected = [
            'feedbacks' => $this->records
                ->when(isset($conditions['type']), fn (Enumerable $records): Enumerable => $records->where('type', $conditions['type']))
                ->when(isset($conditions['status']), fn (Enumerable $records): Enumerable => $records->where('status', $conditions['status']))
                ->when(isset($conditions['sort']), fn (Enumerable $records): Enumerable => $sortBy($records, $conditions['sort']))
                ->map(fn (Record $record): array => [
                    'identifier' => $record->identifier,
                    'type' => $record->type,
                    'status' => $record->status,
                    'content' => $record->content,
                    'createdAt' => $record->created_at->format(\DATE_ATOM),
                    'updatedAt' => $record->updated_at->format(\DATE_ATOM),
                ])
                ->values()
                ->all()
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI($conditions, $accessToken),
            Role::ADMIN
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideConditions(): \Generator
    {
        yield 'empty' => [[]];

        yield 'type' => [['type' => Collection::make(FeedbackType::cases())->random()->name]];

        yield 'status' => [['status' => Collection::make(FeedbackStatus::cases())->random()->name]];

        yield 'sort' => [['sort' => Collection::make(Sort::cases())->random()->name]];

        yield 'full' => [
            [
                'type' => Collection::make(FeedbackType::cases())->random()->name,
                'status' => Collection::make(FeedbackStatus::cases())->random()->name,
                'sort' => Collection::make(Sort::cases())->random()->name,
            ],
        ];
    }

    /**
     * @testdox testListFailureReturnsUnauthorizedWithoutAuthentication フィードバック一覧取得APIを未認証で実行すると401エラーが返却されること.
     */
    public function testListFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitListAPI();

        $response->assertUnauthorized();
    }

    /**
     * ユーザー権限を提供するプロバイダ.
     */
    public static function provideUserRole(): \Generator
    {
        yield 'user' => [Role::USER];

        yield 'admin' => [Role::ADMIN];
    }

    /**
     * テストに使用するレコードを生成する.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * フィードバック追加APIを実行するへルパ.
     */
    private function hitCreateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->postJson(
            uri: '/api/feedbacks',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * フィードバック更新APIを実行するへルパ.
     */
    private function hitUpdateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->putJson(
            uri: \sprintf('/api/feedbacks/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * フィードバック取得APIを実行するへルパ.
     */
    private function hitFindAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->getJson(
            uri: \sprintf('/api/feedbacks/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * フィードバック一覧取得APIを実行するへルパ.
     */
    private function hitListAPI(array $conditions = [], string|null $accessToken = null): TestResponse
    {
        return $this->getJson(
            uri: \sprintf('/api/feedbacks?%s', \http_build_query($conditions)),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 永続化内容を比較する.
     */
    private function assertPersisted(array $payload): void
    {
        $this->assertDatabaseHas('feedbacks', [
            'identifier' => $payload['identifier'],
            'type' => $payload['type'],
            'status' => $payload['status'],
            'content' => $payload['content'],
            'created_at' => $payload['createdAt'],
            'updated_at' => $payload['updatedAt'],
        ]);
    }
}
