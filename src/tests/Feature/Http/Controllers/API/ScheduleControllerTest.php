<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Http\Encoders\Schedule\ScheduleEncoder;
use App\Infrastructures\Schedule\Models\Schedule as Record;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\WithAuthenticationCallable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;
use Tests\TestCase;

/**
 * @group feature
 * @group http
 * @group controllers
 * @group api
 * @group schedule
 *
 * @coversNothing
 */
class ScheduleControllerTest extends TestCase
{
    use DependencyBuildable;
    use FactoryResolvable;
    use NullableValueComparable;
    use RefreshDatabase;
    use WithAuthenticationCallable;

    /**
     * テストに使用するレコード.
     */
    private Enumerable|null $records;

    /**
     * テストに使用するスケジュールエンコーダ.
     */
    private ScheduleEncoder $encoder;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(ScheduleEncoder::class);
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
     * @testdox testAddSuccessPersistSchedule スケジュール追加APIで新規のスケジュールを追加できること.
     * @dataProvider provideRole
     */
    public function testAddSuccessPersistSchedule(Role $role): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
          'user' => $this->builder()->create(UserIdentifier::class, null, [
            'value' => $record->user,
          ]),
        ]);

        $payload = $this->encoder->encode($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            $role
        );

        $response->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddFailureReturnsUnauthorizedWithoutAuthentication スケジュール追加APIで未認証の場合401エラーを返すこと.
     */
    public function testAddFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitAddAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testUpdateSuccessPersistSchedule スケジュール更新APIでスケジュールを更新できること.
     * @dataProvider provideRole
     */
    public function testUpdateSuccessPersistSchedule(Role $role): void
    {
        $record = $this->records->random();

        $entity = $this->builder()->create(Entity::class, null, [
          'identifier' => $this->builder()->create(ScheduleIdentifier::class, null, [
            'value' => $record->identifier,
          ]),
          'user' => $this->builder()->create(UserIdentifier::class, null, [
            'value' => $record->user,
          ]),
        ]);

        $payload = $this->encoder->encode($entity);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            $role
        );

        $response->assertNoContent();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testUpdateFailureReturnsNotFoundWithMissingIdentifier スケジュール更新APIで存在しない識別子を与えたとき404エラーを返すこと.
     */
    public function testUpdateFailureReturnsNotFoundWithMissingIdentifier(): void
    {
        $this->markTestSkipped('persistメソッドをaddとupdateで分けた後に実装.');
    }

    /**
     * @testdox testUpdateFailureReturnsUnauthorizedWithoutAuthentication スケジュール更新APIで未認証の場合401エラーを返すこと.
     */
    public function testUpdateFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitUpdateAPI(['identifier' => Uuid::uuid7()->toString()]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testFindSuccessReturnsSuccessfulResponse スケジュール取得APIでスケジュールを取得できること.
     * @dataProvider provideRole
     */
    public function testFindSuccessReturnsSuccessfulResponse(Role $role): void
    {
        $record = $this->records->random();

        $expected = $this->createFindExpectedResult($record);

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($record->identifier, $accessToken),
            $role
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testFindFailureReturnsNotFoundWithMissingIdentifier スケジュール取得APIで存在しない識別子を与えたとき404エラーを返すこと.
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
     * @testdox testFindFailureReturnsUnauthorizedWithoutAuthentication スケジュール取得APIで未認証の場合401エラーを返すこと.
     */
    public function testFindFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitFindAPI(Uuid::uuid7()->toString());

        $response->assertUnauthorized();
    }

    /**
     * @testdox testListSuccessReturnsSuccessfulResponse スケジュール一覧取得APIでスケジュール一覧を取得できること.
     * @dataProvider provideListValues
     */
    public function testListSuccessReturnsSuccessfulResponse(array $conditions): void
    {
        $filtered = $this->records
          ->when(isset($conditions['status']), fn (Enumerable $records): Enumerable => $records->where('status', $conditions['status']))
          ->when(
              isset($conditions['date']) && !\is_null($conditions['date']['start']),
              fn (Enumerable $records): Enumerable => $records->where('start', '>=', $conditions['date']['start'])
          )
          ->when(
              isset($conditions['date']) && !\is_null($conditions['date']['end']),
              fn (Enumerable $records): Enumerable => $records->where('end', '<=', $conditions['date']['end'])
          );

        $expected = [
          'schedules' => $filtered
            ->map(fn (Record $record): array => $this->createFindExpectedResult($record))
            ->values()
            ->all(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI($conditions, $accessToken),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListFailureReturnsUnauthorizedWithoutAuthentication スケジュール一覧取得APIで未認証の場合401エラーを返すこと.
     */
    public function testListFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitListAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteSuccessDeleteSchedule スケジュール削除APIでスケジュールを削除できること.
     * @dataProvider provideRole
     */
    public function testDeleteSuccessDeleteSchedule(Role $role): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            $role
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('schedules', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteFailureReturnsNotFoundWithMissingIdentifier スケジュール削除APIで存在しない識別子を与えたとき404エラーを返すこと.
     * @dataProvider provideRole
     */
    public function testDeleteFailureReturnsNotFoundWithMissingIdentifier(Role $role): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(Uuid::uuid7()->toString(), $accessToken),
            $role
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testDeleteFailureReturnsUnauthorizedWithoutAuthentication スケジュール削除APIで未認証の場合401エラーを返すこと.
     */
    public function testDeleteFailureReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitDeleteAPI(Uuid::uuid7()->toString());

        $response->assertUnauthorized();
    }

    /**
     * スケジュール一覧取得APIで使用する値を提供するプロパイダ.
     */
    public static function provideListValues(): \Generator
    {
        yield 'empty' => [[]];

        yield 'status' => [['status' => Collection::make(ScheduleStatus::cases())->random()->name]];

        yield 'date' => [[
          'date' => [
            'start' => CarbonImmutable::yesterday()->toAtomString(),
            'end' => CarbonImmutable::tomorrow()->toAtomString()
          ],
        ]];
    }

    /**
     * テストに使用するレコードを生成する.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * スケジュール追加APIを実行する.
     */
    private function hitAddAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: '/api/schedules',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * スケジュール更新APIを実行する.
     */
    private function hitUpdateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('/api/schedules/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * スケジュール取得APIを実行する.
     */
    private function hitFindAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/schedules/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * スケジュール一覧取得APIを実行する.
     */
    private function hitListAPI(array $conditions = [], string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/schedules?%s', \http_build_query($conditions)),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * スケジュール削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('/api/schedules/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 永続化した内容を検証する.
     */
    private function assertPersisted(array $payload): void
    {
        $this->assertDatabaseHas('schedules', [
          'identifier' => $payload['identifier'],
          'user' => $payload['user'],
          'customer' => $payload['customer'],
          'title' => $payload['title'],
          'description' => $payload['description'],
          'start' => $payload['date']['start'],
          'end' => $payload['date']['end'],
          'status' => $payload['status'],
          'repeat' => \is_null($payload['repeatFrequency']) ?
            null : \json_encode($payload['repeatFrequency']),
        ]);
    }

    /**
     * スケジュール取得APIの期待結果を生成する.
     */
    private function createFindExpectedResult(Record $record): array
    {
        return [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'customer' => $record->customer,
          'title' => $record->title,
          'description' => $record->description,
          'date' => [
            'start' => $record->start->format(\DATE_ATOM),
            'end' => $record->end->format(\DATE_ATOM),
          ],
          'status' => $record->status,
          'repeatFrequency' => \is_null($record->repeat) ?
            null : \json_decode($record->repeat),
        ];
    }
}
