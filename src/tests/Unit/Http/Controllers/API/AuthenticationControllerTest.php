<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Exceptions\InvalidTokenException;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Encoders\Authentication\AuthenticationEncoder;
use App\Http\Requests\API\Authentication\LoginRequest;
use App\Http\Requests\API\Authentication\LogoutRequest;
use App\Http\Requests\API\Authentication\RefreshRequest;
use App\Http\Requests\API\Authentication\TokenRequest;
use App\UseCases\Authentication as UseCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Http\RequestGeneratable;
use Tests\TestCase;

/**
 * @group unit
 * @group http
 * @group api
 * @group authentication
 *
 * @coversNothing
 */
class AuthenticationControllerTest extends TestCase
{
    use DependencyBuildable;
    use RequestGeneratable;

    /**
     * 認証エンコーダインスタンス。
     */
    private AuthenticationEncoder $encoder;

    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        parent::setUp();

        $this->encoder = $this->builder()->create(AuthenticationEncoder::class);
    }

    /**
     * @testdox testInstantiateSuccess 正しい値によってインスタンスが生成できること.
     */
    public function testInstantiateSuccess(): void
    {
        $controller = new AuthenticationController();

        $this->assertInstanceOf(AuthenticationController::class, $controller);
    }

    /**
     * @testdox testLoginReturnsSuccessfulResponse loginメソッドで正常な値を与えたとき認証情報を取得できること.
     */
    public function testLoginReturnsSuccessfulResponse(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createUseCase();


        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'email' => 'test@test.com',
          'password' => 'password',
        ];

        $request = $this->createJsonRequest(LoginRequest::class, $payload);

        $actual = $controller->login($request, $useCase, $this->encoder);

        $this->assertIsArray($actual);
        $this->assertSame($payload['identifier'], $actual['identifier']);

        $this->assertArrayHasKey('accessToken', $actual);
        $this->assertSame(TokenType::REFRESH->name, $actual['refreshToken']['type']);

        $this->assertArrayHasKey('refreshToken', $actual);
        $this->assertSame(TokenType::ACCESS->name, $actual['accessToken']['type']);
    }

    /**
     * @testdox testLoginThrowsAccessDeniedHttpWhenAuthorizationWasThrown loginメソッドで認証エラーが発生したときアクセス拒否例外がスローされること.
     */
    public function testLoginThrowsAccessDeniedHttpWhenAuthorizationWasThrown(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->willThrowException(new AuthorizationException());

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'email' => 'test@test.com',
          'password' => 'password',
        ];

        $request = $this->createJsonRequest(LoginRequest::class, $payload);

        $this->expectException(AccessDeniedHttpException::class);

        $controller->login($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testLoginThrowsBadRequestWhenUniqueConstraintViolationExceptionWasThrown loginメソッドで一意制約違反が発生したとき不正なリクエスト例外がスローされること.
     */
    public function testLoginThrowsBadRequestWhenUniqueConstraintViolationExceptionWasThrown(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('persist')
          ->willThrowException($this->createMock(UniqueConstraintViolationException::class));

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
          'email' => 'test@test.com',
          'password' => 'password',
        ];

        $request = $this->createJsonRequest(LoginRequest::class, $payload);

        $this->expectException(BadRequestHttpException::class);

        $controller->login($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testLogoutReturnsSuccessfulResponse logoutメソッドで正常な値を与えたときログアウトできること.
     */
    public function testLogoutReturnsSuccessfulResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $controller = new AuthenticationController();

        $useCase = $this->createUseCase(['instances' => new Collection([$instance])]);

        $payload = [
          'identifier' => $instance->identifier->value(),
        ];

        $request = $this->createJsonRequest(LogoutRequest::class, $payload);

        $actual = $controller->logout($request, $useCase);

        $this->assertSame([], $actual);
    }

    /**
     * @testdox testLogoutThrowsNotFoundWhenOutOfBoundsExceptionWasThrown logoutメソッドで存在しない認証情報を指定したとき例外がスローされること.
     */
    public function testLogoutThrowsNotFoundWhenOutOfBoundsExceptionWasThrown(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('logout')
          ->willThrowException(new \OutOfBoundsException());

        $payload = [
          'identifier' => Uuid::uuid7()->toString(),
        ];

        $request = $this->createJsonRequest(LogoutRequest::class, $payload);

        $this->expectException(NotFoundHttpException::class);

        $controller->logout($request, $useCase);
    }

    /**
     * @testdox testIntrospectReturnsSuccessfulResponse introspectionメソッドで正常な値を与えたとき有効なトークンであること.
     *
     * @dataProvider provideIntrospectValues
     */
    public function testIntrospectReturnsSuccessfulResponse(array $overrides, bool $expected): void
    {
        $instance = $this->builder()->create(Entity::class, null, [
          'accessToken' => $this->builder()->create(Token::class, null, $overrides['accessToken']),
          'refreshToken' => $this->builder()->create(Token::class, null, $overrides['refreshToken']),
        ]);

        $controller = new AuthenticationController();

        $useCase = $this->createUseCase(['instances' => new Collection([$instance])]);

        $createPayload = fn (Token $token) => [
          'token' => [
            'type' => $token->type()->name,
            'value' => $token->value(),
            'expiresAt' => $token->expiresAt()->toAtomString(),
          ]
        ];

        $payload1 = $createPayload($instance->accessToken());
        $payload2 = $createPayload($instance->refreshToken());

        $request1 = $this->createJsonRequest(TokenRequest::class, $payload1);
        $request2 = $this->createJsonRequest(TokenRequest::class, $payload2);

        $actual1 = $controller->introspect($request1, $useCase);
        $actual2 = $controller->introspect($request2, $useCase);

        $this->assertSame($expected, $actual1['active']);
        $this->assertSame($expected, $actual2['active']);
    }

    /**
     * introspectメソッドのテストデータと期待結果を提供するプロバイダ.
     */
    public static function provideIntrospectValues(): \Generator
    {
        yield 'active token' => [
          'overrides' => [
            'accessToken' =>  [
              'type' => TokenType::ACCESS,
              'expiresAt' => now()->addDay(),
            ],
            'refreshToken' => [
              'type' => TokenType::REFRESH,
              'expiresAt' => now()->addDay(),
            ],
          ],
          'expected' => true,
        ];

        yield 'inactive token' => [
          'overrides' => [
            'accessToken' =>  [
              'type' => TokenType::ACCESS,
              'expiresAt' => now()->subDay(),
            ],
            'refreshToken' => [
              'type' => TokenType::REFRESH,
              'expiresAt' => now()->subDay(),
            ],
          ],
          'expected' => false,
        ];
    }

    /**
     * @testdox testIntrospectThrowsNotFoundWhenInvalidTokenWasThrown introspectionメソッドで無効なトークンを与えたとき例外がスローされること.
     */
    public function testIntrospectThrowsNotFoundWhenInvalidTokenWasThrown(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('introspection')
          ->willThrowException(new InvalidTokenException());

        $payload = [
          'token' => [
            'type' => TokenType::ACCESS->name,
            'value' => Uuid::uuid7()->toString(),
            'expiresAt' => now()->toAtomString(),
          ]
        ];

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $this->expectException(BadRequestHttpException::class);

        $controller->introspect($request, $useCase);
    }

    /**
     * @testdox testRefreshReturnsSuccessfulResponse refreshメソッドで正常な値を与えたとき認証を更新できること.
     */
    public function testRefreshReturnsSuccessfulResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $controller = new AuthenticationController();

        $useCase = $this->createUseCase(['instances' => new Collection([$instance])]);

        $payload = [
          'token' => [
            'type' => TokenType::REFRESH->name,
            'value' => $instance->refreshToken()->value(),
            'expiresAt' => $instance->refreshToken()->expiresAt()->toAtomString(),
          ]
        ];

        $request = $this->createJsonRequest(RefreshRequest::class, $payload);

        $actual = $controller->refresh($request, $useCase, $this->encoder);

        $this->assertIsArray($actual);
        $this->assertArrayHasKey('accessToken', $actual);
        $this->assertArrayHasKey('refreshToken', $actual);

        $this->assertSame(TokenType::ACCESS->name, $actual['accessToken']['type']);
        $this->assertSame(TokenType::REFRESH->name, $actual['refreshToken']['type']);

        $this->assertNotSame($instance->accessToken()->value(), $actual['accessToken']['value']);
        $this->assertNotSame($instance->refreshToken()->value(), $actual['refreshToken']['value']);
    }

    /**
     * @testdox testRefreshThrowsBadRequestWhenInvalidTokenWasThrown refreshメソッドで無効なトークンを与えたとき不正なリクエスト例外がスローされること.
     */
    public function testRefreshThrowsBadRequestWhenInvalidTokenWasThrown(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('refresh')
          ->willThrowException(new InvalidTokenException());

        $payload = [
          'token' => [
            'type' => TokenType::REFRESH->name,
            'value' => Uuid::uuid7()->toString(),
            'expiresAt' => now()->toAtomString(),
          ]
        ];

        $request = $this->createJsonRequest(RefreshRequest::class, $payload);

        $this->expectException(BadRequestHttpException::class);

        $controller->refresh($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testRevokeReturnsSuccessfulResponse revokeメソッドで正常な値を与えたとき認証情報を破棄できること.
     */
    public function testRevokeReturnsSuccessfulResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $controller = new AuthenticationController();

        $useCase = $this->createUseCase(['instances' => new Collection([$instance])]);

        $payload = [
          'token' => [
            'type' => TokenType::ACCESS->name,
            'value' => $instance->accessToken()->value(),
            'expiresAt' => $instance->accessToken()->expiresAt()->toAtomString(),
          ]
        ];

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $actual = $controller->revoke($request, $useCase);

        $this->assertSame([], $actual);
    }

    /**
     * @testdox testRevokeThrowsBadRequestWhenInvalidTokenWasThrown revokeメソッドで無効なトークンを与えたとき不正なリクエスト例外がスローされること.
     */
    public function testRevokeThrowsBadRequestWhenInvalidTokenWasThrown(): void
    {
        $controller = new AuthenticationController();

        $useCase = $this->createMock(UseCase::class);
        $useCase
          ->expects($this->once())
          ->method('revoke')
          ->willThrowException(new InvalidTokenException());

        $payload = [
          'token' => [
            'type' => TokenType::ACCESS->name,
            'value' => Uuid::uuid7()->toString(),
            'expiresAt' => now()->toAtomString(),
          ]
        ];

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $this->expectException(BadRequestHttpException::class);

        $controller->revoke($request, $useCase);
    }

    /**
     * ユースケースを生成するへルパ.
     */
    private function createUseCase(array $overrides = []): UseCase
    {
        return $this->builder()->create(UseCase::class, null, $overrides);
    }
}
