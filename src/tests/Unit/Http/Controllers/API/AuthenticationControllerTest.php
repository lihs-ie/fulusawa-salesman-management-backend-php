<?php

namespace Tests\Unit\Http\Controllers\API;

use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Exceptions\InvalidTokenException;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Encoders\Authentication\AuthenticationEncoder;
use App\Http\Requests\API\Authentication\LoginRequest;
use App\Http\Requests\API\Authentication\LogoutRequest;
use App\Http\Requests\API\Authentication\RefreshRequest;
use App\Http\Requests\API\Authentication\TokenRequest;
use App\UseCases\Authentication as UseCase;
use Faker\Factory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Response;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
 *
 * @internal
 */
class AuthenticationControllerTest extends TestCase
{
    use DependencyBuildable;
    use RequestGeneratable;

    /**
     * テストに使用するインスタンス.
     */
    private ?Enumerable $instances;

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

        $this->instances = $this->createInstances();
        $this->encoder = $this->builder()->create(AuthenticationEncoder::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->instances = null;

        parent::tearDown();
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

        $entity = $this->builder()->create(Entity::class);

        $payload = [
            'identifier' => $entity->identifier->value(),
            'email' => Factory::create()->email,
            'password' => Str::random(\mt_rand(8, 16)),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('persist')
            ->with(...$payload)
            ->willReturn($entity)
        ;

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

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'email' => 'test@test.com',
            'password' => 'password',
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('persist')
            ->with(...$payload)
            ->willThrowException(new AuthorizationException())
        ;

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

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
            'email' => 'test@test.com',
            'password' => 'password',
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('persist')
            ->with(...$payload)
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class))
        ;

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

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('logout')
            ->with($instance->identifier->value())
        ;

        $payload = [
            'identifier' => $instance->identifier->value(),
        ];

        $request = $this->createJsonRequest(LogoutRequest::class, $payload);

        $actual = $controller->logout($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
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
            ->willThrowException(new \OutOfBoundsException())
        ;

        $payload = [
            'identifier' => Uuid::uuid7()->toString(),
        ];

        $request = $this->createJsonRequest(LogoutRequest::class, $payload);

        $this->expectException(NotFoundHttpException::class);

        $controller->logout($request, $useCase);
    }

    /**
     * @testdox testIntrospectReturnsSuccessfulResponse introspectionメソッドで正常な値を与えたとき有効なトークンであること.
     */
    public function testIntrospectReturnsSuccessfulResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = [
            'value' => $instance->accessToken()->value(),
            'type' => $instance->accessToken()->type()->name,
        ];

        $expected = [
            'active' => (bool) \mt_rand(0, 1),
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('introspection')
            ->with(...$payload)
            ->willReturn($expected['active'])
        ;

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $controller = new AuthenticationController();

        $actual = $controller->introspect($request, $useCase);

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testIntrospectThrowsNotFoundWhenInvalidTokenWasThrown introspectionメソッドで無効なトークンを与えたとき例外がスローされること.
     */
    public function testIntrospectThrowsNotFoundWhenInvalidTokenWasThrown(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = [
            'value' => $instance->accessToken()->value(),
            'type' => $instance->accessToken()->type()->name,
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('introspection')
            ->with(...$payload)
            ->willThrowException(new InvalidTokenException())
        ;

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $controller = new AuthenticationController();

        $this->expectException(BadRequestHttpException::class);

        $controller->introspect($request, $useCase);
    }

    /**
     * @testdox testRefreshReturnsSuccessfulResponse refreshメソッドで正常な値を与えたとき認証を更新できること.
     */
    public function testRefreshReturnsSuccessfulResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $next = $this->builder()->create(class: Entity::class, overrides: [
            'identifier' => $instance->identifier,
        ]);

        $expected = $this->encoder->encode($next);

        $payload = [
            'value' => Hash::make($instance->refreshToken()->value()),
            'type' => TokenType::REFRESH->name,
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('refresh')
            ->with(...$payload)
            ->willReturn($next)
        ;

        $request = $this->createJsonRequest(RefreshRequest::class, $payload);

        $controller = new AuthenticationController();

        $actual = $controller->refresh($request, $useCase, $this->encoder);

        $this->assertSame($expected, $actual);
    }

    /**
     * @testdox testRefreshThrowsBadRequestWhenInvalidTokenWasThrown refreshメソッドで無効なトークンを与えたとき不正なリクエスト例外がスローされること.
     */
    public function testRefreshThrowsBadRequestWhenInvalidTokenWasThrown(): void
    {
        $payload = [
            'value' => Uuid::uuid7()->toString(),
            'type' => TokenType::REFRESH->name,
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('refresh')
            ->with(...$payload)
            ->willThrowException(new InvalidTokenException())
        ;

        $request = $this->createJsonRequest(RefreshRequest::class, $payload);

        $this->expectException(BadRequestHttpException::class);

        $controller = new AuthenticationController();

        $controller->refresh($request, $useCase, $this->encoder);
    }

    /**
     * @testdox testRevokeReturnsSuccessfulResponse revokeメソッドで正常な値を与えたとき認証情報を破棄できること.
     */
    public function testRevokeReturnsSuccessfulResponse(): void
    {
        $instance = $this->builder()->create(Entity::class);

        $payload = [
            'value' => $instance->accessToken()->value(),
            'type' => TokenType::ACCESS->name,
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('revoke')
            ->with(...$payload)
        ;

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $controller = new AuthenticationController();

        $actual = $controller->revoke($request, $useCase);

        $this->assertInstanceOf(Response::class, $actual);
        $this->assertSame(Response::HTTP_NO_CONTENT, $actual->getStatusCode());
    }

    /**
     * @testdox testRevokeThrowsBadRequestWhenInvalidTokenWasThrown revokeメソッドで無効なトークンを与えたとき不正なリクエスト例外がスローされること.
     */
    public function testRevokeThrowsBadRequestWhenInvalidTokenWasThrown(): void
    {
        $payload = [
            'value' => Uuid::uuid7()->toString(),
            'type' => TokenType::ACCESS->name,
        ];

        $useCase = $this->createMock(UseCase::class);
        $useCase
            ->expects($this->once())
            ->method('revoke')
            ->with(...$payload)
            ->willThrowException(new InvalidTokenException())
        ;

        $request = $this->createJsonRequest(TokenRequest::class, $payload);

        $controller = new AuthenticationController();

        $this->expectException(BadRequestHttpException::class);

        $controller->revoke($request, $useCase);
    }

    /**
     * テストに使用するインスタンスを生成する.
     */
    private function createInstances(): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10));
    }
}
