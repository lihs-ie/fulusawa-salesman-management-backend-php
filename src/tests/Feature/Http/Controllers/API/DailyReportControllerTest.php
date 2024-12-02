<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Domains\User\ValueObjects\Role;
use App\Infrastructures\DailyReport\Models\DailyReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\Support\Helpers\Http\WithAuthenticationCallable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;
use Tests\TestCase;

/**
 * @group feature
 * @group http
 * @group controllers
 * @group api
 * @group dailyreport
 *
 * @coversNothing
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class DailyReportControllerTest extends TestCase
{
    use FactoryResolvable;
    use RefreshDatabase;
    use WithAuthenticationCallable;

    /**
     * テストに使用するレコード.
     */
    private Enumerable|null $records;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->records = $this->createRecords();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->records = null;

        parent::tearDown();
    }

    /**
     * @testdox testAddSuccessReturnsSuccessResponse 日報追加APIに日報を新規作成できること.
     */
    public function testAddSuccessReturnsSuccessResponse(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertCreated();
        $this->assertPersisted($payload);
    }

    /**
     * @testdox testAddReturnsForbiddenWithRoleOfAdmin 日報追加APIに管理者権限でリクエストするとForbiddenが返ること.
     */
    public function testAddReturnsForbiddenWithRoleOfAdmin(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            Role::ADMIN
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testAddReturnsUnauthorizedWithoutAuthentication 日報追加APIに未認証でリクエストするとBadRequestが返ること.
     */
    public function testAddReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->hitAddAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testAddReturnsConflictWithExistingIdentifier 日報追加APIで既存のidentifierを指定するとConflictが返ること.
     */
    public function testAddReturnsConflictWithExistingIdentifier(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitAddAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertConflict();
    }

    /**
     * @testdox testUpdateSuccessReturnsSuccessResponse updateメソッドで日報を更新できること.
     */
    public function testUpdateSuccessReturnsSuccessResponse(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertSuccessful();
        $response->assertStatus(204);

        $this->assertDatabaseHas('daily_reports', [
          'identifier' => $payload['identifier'],
          'user' => $payload['user'],
          'date' => $payload['date'],
          'schedules' => \json_encode($payload['schedules']),
          'visits' => \json_encode($payload['visits']),
          'is_submitted' => $payload['isSubmitted']
        ]);
    }

    /**
     * @testdox testUpdateReturnsNotFoundWithMissingIdentifier updateメソッドで存在しない日報を更新するとNotFoundが返ること.
     */
    public function testUpdateReturnsNotFoundWithMissingIdentifier(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::USER
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testUpdateReturnsForbiddenWithRoleOfAdmin 日報更新APIを管理者権限でリクエストするとForbiddenが返ること.
     */
    public function testUpdateReturnsForbiddenWithRoleOfAdmin(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitUpdateAPI($payload, $accessToken),
            Role::ADMIN
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testUpdateReturnsUnauthorizedWithoutAuthentication 日報更新APIを認証なしでリクエストするとUnauthorizedが返ること.
     */
    public function testUpdateReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $payload = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'date' => CarbonImmutable::now()->toDateString(),
          'schedules' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'visits' => Collection::times(\mt_rand(1, 3), fn (): string => Uuid::uuid7()->toString())
            ->all(),
          'isSubmitted' => (bool) \mt_rand(0, 1)
        ];

        $response = $this->hitUpdateAPI($payload);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testFindSuccessReturnsSuccessResponse 日報取得APIで日報を取得できること.
     */
    public function testFindSuccessReturnsSuccessResponse(): void
    {
        $record = $this->records->random();

        $expected = [
          'identifier' => $record->identifier,
          'user' => $record->user,
          'date' => CarbonImmutable::parse($record->date)->toAtomString(),
          'schedules' => \json_decode($record->schedules),
          'visits' => \json_decode($record->visits),
          'isSubmitted' => $record->is_submitted
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI($record->identifier, $accessToken),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testFindReturnsNotFoundWithMissingIdentifier 日報取得APIで存在しない日報を取得するとNotFoundが返ること.
     */
    public function testFindReturnsNotFoundWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitFindAPI(Uuid::uuid7()->toString(), $accessToken),
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testFindReturnsUnauthorizedWithoutAuthentication 日報取得APIで認証なしでリクエストするとUnauthorizedが返ること.
     */
    public function testFindReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $response = $this->hitFindAPI($record->identifier);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testListSuccessReturnsSuccessResponseWithoutCondition 日報一覧取得APIで検索条件を指定せずにリクエストしたとき全ての日報を取得できること.
     */
    public function testListSuccessReturnsSuccessResponseWithoutCondition(): void
    {
        $expected = [
          'dailyReports' => $this->records
            ->map(fn (DailyReport $record): array => [
              'identifier' => $record->identifier,
              'user' => $record->user,
              'date' => CarbonImmutable::parse($record->date)->toAtomString(),
              'schedules' => \json_decode($record->schedules),
              'visits' => \json_decode($record->visits),
              'isSubmitted' => $record->is_submitted
            ])
            ->all()
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI([], $accessToken),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListSuccessReturnsSuccessResponseWithCondition 日報一覧取得APIで検索条件を指定してリクエストしたとき条件に合致する日報を取得できること.
     */
    public function testListSuccessReturnsSuccessResponseWithCondition(): void
    {
        $target = $this->records->random();

        $expected = [
          'dailyReports' => $this->records
            ->filter(fn (DailyReport $record): bool => $record->user === $target->user)
            ->map(fn (DailyReport $record): array => [
              'identifier' => $record->identifier,
              'user' => $record->user,
              'date' => CarbonImmutable::parse($record->date)->toAtomString(),
              'schedules' => \json_decode($record->schedules),
              'visits' => \json_decode($record->visits),
              'isSubmitted' => $record->is_submitted
            ])
            ->values()
            ->all()
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitListAPI(['user' => $target->user], $accessToken),
        );

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testListReturnsUnauthorizedWithoutAuthentication 日報一覧取得APIで認証なしでリクエストするとUnauthorizedが返ること.
     */
    public function testListReturnsUnauthorizedWithoutAuthentication(): void
    {
        $response = $this->hitListAPI([]);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testDeleteSuccessReturnsSuccessResponse 日報削除APIで日報を削除できること.
     */
    public function testDeleteSuccessReturnsSuccessResponse(): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::ADMIN
        );

        $response->assertSuccessful();
        $response->assertStatus(204);

        $this->assertDatabaseMissing('daily_reports', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteReturnsNotFoundWithMissingIdentifier 日報削除APIで存在しない日報を削除するとNotFoundが返ること.
     */
    public function testDeleteReturnsNotFoundWithMissingIdentifier(): void
    {
        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI(Uuid::uuid7()->toString(), $accessToken),
            Role::ADMIN
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testDeleteReturnsForbiddenWithRoleOfUser 日報削除APIをユーザー権限でリクエストするとForbiddenが返ること.
     */
    public function testDeleteReturnsForbiddenWithRoleOfUser(): void
    {
        $record = $this->records->random();

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitDeleteAPI($record->identifier, $accessToken),
            Role::USER
        );

        $response->assertForbidden();
    }

    /**
     * @testdox testDeleteReturnsUnauthorizedWithoutAuthentication 日報削除APIで認証なしでリクエストするとUnauthorizedが返ること.
     */
    public function testDeleteReturnsUnauthorizedWithoutAuthentication(): void
    {
        $record = $this->records->random();

        $response = $this->hitDeleteAPI($record->identifier);

        $response->assertUnauthorized();
    }

    /**
     * テストに使用するレコードを生成するへルパ.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(DailyReport::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * 日報追加APIを実行する.
     */
    private function hitAddAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'POST',
            uri: '/api/daily-reports',
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 日報更新APIを実行する.
     */
    private function hitUpdateAPI(array $payload, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'PUT',
            uri: \sprintf('/api/daily-reports/%s', $payload['identifier']),
            data: $payload,
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 日報取得APIを実行する.
     */
    private function hitFindAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/daily-reports/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 日報一覧取得APIを実行する.
     */
    private function hitListAPI(array $conditions = [], string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'GET',
            uri: \sprintf('/api/daily-reports?%s', \http_build_query($conditions)),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 日報削除APIを実行する.
     */
    private function hitDeleteAPI(string $identifier, string|null $accessToken = null): TestResponse
    {
        return $this->json(
            method: 'DELETE',
            uri: \sprintf('/api/daily-reports/%s', $identifier),
            headers: \is_null($accessToken) ? [] : ['Authorization' => "Bearer {$accessToken}"]
        );
    }

    /**
     * 永続化内容を比較する.
     */
    private function assertPersisted(array $payload): void
    {
        $this->assertDatabaseHas('daily_reports', [
          'identifier' => $payload['identifier'],
          'user' => $payload['user'],
          'date' => $payload['date'],
          'schedules' => \json_encode($payload['schedules']),
          'visits' => \json_encode($payload['visits']),
          'is_submitted' => $payload['isSubmitted']
        ]);
    }
}
