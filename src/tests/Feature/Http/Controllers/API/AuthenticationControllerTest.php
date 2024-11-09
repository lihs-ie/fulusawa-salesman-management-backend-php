<?php

namespace Tests\Feature\Http\Controllers\API;

use App\Infrastructures\Authentication\Models\Authentication as Record;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;
use Tests\TestCase;

/**
 * @group feature
 * @group http
 * @group api
 * @group authentication
 *
 * @coversNothing
 */
class AuthenticationControllerTest extends TestCase
{
    use DependencyBuildable;
    use FactoryResolvable;
    use RefreshDatabase;

    /**
     * テストに使用するレコード.
     */
    private Enumerable|null $records;

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
          ->first();

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

        $response->assertForbidden();
    }

    /**
     * @testdox testLoginReturnsBadRequestResponseWithDuplicateIdentifier loginAPIに重複したidentifierを与えたとき400レスポンスが返却されること.
     */
    public function testLoginReturnsBadRequestResponseWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord()
          ->with('user')
          ->first();

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

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitLogoutAPI($payload, $header);

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
        $authentication = $this->getAuthentication();

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitLogoutAPI($payload, $header);

        $response->assertNotFound();
    }

    /**
     * @testdox testIntrospectReturnsSuccessfulResponseWithValidAccessToken introspectAPIに正常なアクセストークンのリクエストを送信すると200レスポンスが返却されること.
     */
    public function testIntrospectReturnsSuccessfulResponseWithValidAccessToken(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
          'token' => $authentication['accessToken']
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitIntrospectAPI($payload, $header);

        $expected = [
          'active' => true,
        ];

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
          'token' => $authentication['refreshToken']
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitIntrospectAPI($payload, $header);

        $expected = [
          'active' => true,
        ];

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
          ->create();

        $payload = [
          'token' => [
            'type' => 'ACCESS',
            'value' => $expired->token,
            'expiresAt' => $expired->expires_at->toAtomString(),
          ]
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $expired->token),
        ];

        $response = $this->hitIntrospectAPI($payload, $header);

        $response->assertUnauthorized();
    }

    /**
     * @testdox testRefreshReturnsSuccessfulResponse refreshAPIに正常なリクエストを送信すると200レスポンスが返却されること.
     */
    public function testRefreshReturnsSuccessfulResponse(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
          'token' => $authentication['refreshToken']
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitRefreshAPI($payload, $header);

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
        $authentication = $this->getAuthentication();

        $expired = $this->factory(Record::class)
          ->bothExpired()
          ->create();

        $payload = [
          'token' => [
            'type' => 'REFRESH',
            'value' => $expired->refresh_token,
            'expiresAt' => $expired->refresh_token_expires_at,
          ]
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitRefreshAPI($payload, $header);

        $response->assertBadRequest();
    }

    /**
     * @testdox testRevokeReturnsSuccessfulResponse revokeAPIに正常なリクエストを送信すると200レスポンスが返却されること.
     */
    public function testRevokeReturnsSuccessfulResponse(): void
    {
        $authentication = $this->getAuthentication();

        $payload = [
          'token' => $authentication['accessToken']
        ];

        $header = [
          'Authorization' => \sprintf('Bearer %s', $authentication['accessToken']['value']),
        ];

        $response = $this->hitRevokeAPI($payload, $header);

        $response->assertSuccessful();
        $response->assertJson([]);

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
    private function hitLogoutAPI(array $payload, array $headers = []): TestResponse
    {
        return $this->json('POST', '/api/auth/logout', $payload, $headers);
    }

    /**
     * イントロスペクトAPIを呼び出す.
     */
    private function hitIntrospectAPI(array $payload, array $headers = []): TestResponse
    {
        return $this->json('POST', '/api/auth/introspect', $payload, $headers);
    }

    /**
     * リフレッシュAPIを呼び出す.
     */
    private function hitRefreshAPI(array $payload, array $headers = []): TestResponse
    {
        return $this->json('POST', '/api/auth/token', $payload, $headers);
    }

    /**
     * リヴォケーションAPIを呼び出す.
     */
    private function hitRevokeAPI(array $payload, array $headers = []): TestResponse
    {
        return $this->json('POST', '/api/auth/revoke', $payload, $headers);
    }

    /**
     * テストに使用する認証情報を取得する.
     */
    private function getAuthentication(): array
    {
        $record = $this->pickRecord()
          ->with('user')
          ->first();

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
          ->createMany(\mt_rand(5, 10));
    }

    /**
     * 生成したレコードを1件取得する.
     */
    private function pickRecord(): Record
    {
        return $this->records->random();
    }
}
