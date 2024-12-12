<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Infrastructures\Authentication\Models\Authentication as Record;
use Carbon\CarbonImmutable;
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
 * @group api
 * @group authentication
 *
 * @coversNothing
 *
 * @internal
 */
class AuthenticationControllerTest extends TestCase
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
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->records = $this->createRecords();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        $this->records = null;

        parent::tearDown();
    }

    /**
     * @testdox testLoginReturnsSuccessfulResponse loginAPIに正常なリクエストを送信すると200レスポンスが返却されること.
     */
    public function testLoginReturnsSuccessfulResponse(): void
    {
        $record = $this->pickRecord()
            ->with('user')
            ->first()
        ;

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'email' => $record->user->email,
            'password' => $record->user->password,
        ];

        $response = $this->hitLoginAPI($payload);

        $response->assertSuccessful();
    }

    /**
     * @testdox testLoginReturnsAccessDeniedResponseWithInvalidCredentials loginAPIに不正なクレデンシャルを与えたとき401レスポンスが返却されること.
     */
    public function testLoginReturnsAccessDeniedResponseWithInvalidCredentials(): void
    {
        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'email' => 'test@test.com',
            'password' => 'invalid-password',
        ];

        $response = $this->hitLoginAPI($payload);

        $response->assertNotFound();
    }

    /**
     * @testdox testLoginReturnsBadRequestResponseWithDuplicateIdentifier loginAPIに重複したidentifierを与えたとき400レスポンスが返却されること.
     */
    public function testLoginReturnsBadRequestResponseWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord()
            ->with('user')
            ->first()
        ;

        $payload = [
            'identifier' => $record->identifier,
            'email' => $record->user->email,
            'password' => $record->user->password,
        ];

        $response = $this->hitLoginAPI($payload);

        $response->assertBadRequest();
    }

    /**
     * @testdox testLogoutReturnsSuccessfulResponse logoutAPIに正常なリクエストを送信すると200レスポンスが返却されること.
     */
    public function testLogoutReturnsSuccessfulResponse(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
            'identifier' => $authentication['identifier'],
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitLogoutAPI($payload, $accessToken)
        );

        $response->assertSuccessful();

        $this->assertDatabaseMissing(
            'authentications',
            ['identifier' => $authentication['identifier']]
        );
    }

    /**
     * @testdox testLogoutReturnsUnauthorizedWithInvalidAccessToken logoutAPIに存在しない識別子のリクエストを与えたとき401レスポンスが返却されること.
     */
    public function testLogoutReturnsNotFoundWithMissingIdentifier(): void
    {
        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitLogoutAPI($payload, $accessToken)
        );

        $response->assertNotFound();
    }

    /**
     * @testdox testIntrospectReturnsSuccessfulResponseWithValidAccessToken introspectAPIに正常なアクセストークンのリクエストを送信すると200レスポンスが返却されること.
     */
    public function testIntrospectReturnsSuccessfulResponseWithValidAccessToken(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
            'type' => 'ACCESS',
            'value' => $authentication['accessToken']['value'],
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitIntrospectAPI($payload, $accessToken)
        );

        $expected = ['active' => true];

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testIntrospectReturnsSuccessfulResponseWithValidRefreshToken introspectAPIに正常なリフレッシュトークンのリクエストを送信すると200レスポンスが返却されること.
     */
    public function testIntrospectReturnsSuccessfulResponseWithValidRefreshToken(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
            'type' => 'REFRESH',
            'value' => $authentication['refreshToken']['value'],
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitIntrospectAPI($payload, $accessToken)
        );

        $expected = ['active' => true];

        $response->assertSuccessful();
        $response->assertJson($expected);
    }

    /**
     * @testdox testIntrospectReturnsUnauthorizedWithInvalidAccessToken introspectAPIに期限切れのトークンのリクエストを送信すると401レスポンスが返却されること.
     */
    public function testIntrospectReturnsUnauthorizedWithExpiredToken(): void
    {
        $expired = $this->factory(Record::class)
            ->bothExpired()
            ->create()
        ;

        $payload = [
            'type' => 'ACCESS',
            'value' => $expired->token,
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitIntrospectAPI($payload, $accessToken)
        );

        $response->assertBadRequest();
    }

    /**
     * @testdox testRefreshReturnsSuccessfulResponse refreshAPIに正常なリクエストを送信すると200レスポンスが返却されること.
     */
    public function testRefreshReturnsSuccessfulResponse(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
            'type' => 'REFRESH',
            'value' => $authentication['refreshToken']['value'],
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitRefreshAPI($payload, $accessToken)
        );

        $response->assertSuccessful();

        $actual = $response->json();

        $this->assertSame($authentication['identifier'], $actual['identifier']);
        $this->assertNotSame($authentication['accessToken']['value'], $actual['accessToken']['value']);
        $this->assertNotSame($authentication['refreshToken']['value'], $actual['refreshToken']['value']);

        $this->assertTrue(CarbonImmutable::parse($actual['accessToken']['expiresAt'])->isFuture());
        $this->assertTrue(CarbonImmutable::parse($actual['refreshToken']['expiresAt'])->isFuture());
    }

    /**
     * @testdox testRefreshReturnsBadRequestWithInvalidToken refreshAPIに期限切れのリフレッシュトークンのリクエストを送信すると401レスポンスが返却されること.
     */
    public function testRefreshReturnsBadRequestWithInvalidToken(): void
    {
        $expired = $this->factory(Record::class)
            ->bothExpired()
            ->create()
        ;

        $payload = [
            'type' => 'REFRESH',
            'value' => Hash::make($expired->refresh_token),
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitRefreshAPI($payload, $accessToken)
        );

        $response->assertBadRequest();
    }

    /**
     * @testdox testRevokeReturnsSuccessfulResponse revokeAPIに正常なリクエストを送信すると200レスポンスが返却されること.
     */
    public function testRevokeReturnsSuccessfulResponse(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
            'type' => 'ACCESS',
            'value' => $authentication['accessToken']['value'],
        ];

        $response = $this->callAPIWithAuthentication(
            fn (string $accessToken): TestResponse => $this->hitRevokeAPI($payload, $accessToken)
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'authentications',
            [
                'identifier' => $authentication['identifier'],
                'token' => null,
                'expires_at' => null,
                'refresh_token' => hash('sha256', $authentication['refreshToken']['value']),
                'refresh_token_expires_at' => $authentication['refreshToken']['expiresAt'],
            ]
        );
    }

    /**
     * ログインAPIを呼び出す.
     */
    private function hitLoginAPI(array $payload): TestResponse
    {
        return $this->json('POST', '/api/auth/login', $payload);
    }

    /**
     * ログアウトAPIを呼び出す.
     */
    private function hitLogoutAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            'POST',
            '/api/auth/logout',
            $payload,
            ['Authorization' => \sprintf('Bearer %s', $accessToken)]
        );
    }

    /**
     * イントロスペクトAPIを呼び出す.
     */
    private function hitIntrospectAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            'POST',
            '/api/auth/introspect',
            $payload,
            ['Authorization' => \sprintf('Bearer %s', $accessToken)]
        );
    }

    /**
     * リフレッシュAPIを呼び出す.
     */
    private function hitRefreshAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            'POST',
            '/api/auth/token',
            $payload,
            ['Authorization' => \sprintf('Bearer %s', $accessToken)]
        );
    }

    /**
     * リヴォケーションAPIを呼び出す.
     */
    private function hitRevokeAPI(array $payload, ?string $accessToken = null): TestResponse
    {
        return $this->json(
            'POST',
            '/api/auth/revoke',
            $payload,
            ['Authorization' => \sprintf('Bearer %s', $accessToken)]
        );
    }

    /**
     * テストに使用する認証情報を取得する.
     */
    private function getAuthentication(): array
    {
        $record = $this->pickRecord()
            ->with('user')
            ->first()
        ;

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'email' => $record->user->email,
            'password' => $record->user->password,
        ];

        $response = $this->hitLoginAPI($payload);

        return $response->json();
    }

    /**
     * テストに使用するレコードを生成する.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)
            ->roleOf()
            ->createMany(\mt_rand(5, 10))
        ;
    }

    /**
     * 生成したレコードを1件取得する.
     */
    private function pickRecord(): Record
    {
        return $this->records->random();
    }
}
