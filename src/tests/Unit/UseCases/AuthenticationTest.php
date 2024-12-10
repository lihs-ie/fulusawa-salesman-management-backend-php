<?php

namespace Tests\Unit\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\Entities\User;
use App\Domains\User\UserRepository;
use App\UseCases\Authentication as UseCase;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group authentication
 *
 * @coversNothing
 *
 * @internal
 */
class AuthenticationTest extends TestCase
{
    use DependencyBuildable;
    use PersistUseCaseTest;

    /**
     * テストに使用する認証インスタンス.
     */
    private Enumerable $authentications;

    /**
     * テストに使用するユーザーインスタンス.
     */
    private Enumerable $users;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authentications = clone $this->createAuthentications();
        $this->users = clone $this->createUsers();
    }

    /**
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規の認証情報を永続化すること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase] = $this->createEmptyPersistedUseCase();

        $user = $this->users->random();

        $actual = $useCase->persist(
            identifier: $expected->identifier()->value(),
            email: $user->email()->value(),
            password: $user->password(),
        );

        $this->assertTrue($expected->identifier()->equals($actual->identifier()));
    }

    /**
     * @testdox testIntrospectionSuccessWithValidAccessToken introspectionメソッドで有効なアクセストークンを与えた時にtrueを返すこと.
     */
    public function testIntrospectionSuccessWithValidAccessToken(): void
    {
        $instance = $this->builder()->create(Entity::class, null, [
            'accessToken' => $this->createToken(TokenType::ACCESS, now()->addHour()),
            'refreshToken' => $this->createToken(TokenType::REFRESH, now()->addHour()),
        ]);

        $this->authentications = clone $this->authentications->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(
            value: $parameters['accessToken']['value'],
            type: $parameters['accessToken']['type']
        );

        $this->assertTrue($actual);
    }

    /**
     * @testdox testIntrospectionSuccessWithValidRefreshToken introspectionメソッドで有効なリフレッシュトークンを与えた時にtrueを返すこと.
     */
    public function testIntrospectionSuccessfulReturnsTrueWithValidRefreshToken(): void
    {
        $instance = $this->builder()->create(Entity::class, null, [
            'accessToken' => $this->createToken(TokenType::ACCESS, now()->addHour()),
            'refreshToken' => $this->createToken(TokenType::REFRESH, now()->addHour()),
        ]);

        $this->authentications = clone $this->authentications->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(
            value: $parameters['refreshToken']['value'],
            type: $parameters['refreshToken']['type']
        );

        $this->assertTrue($actual);
    }

    /**
     * @testdox testIntrospectionSuccessReturnsFalseWithExpiredAccessToken introspectionメソッドで有効期限切れのアクセストークンを与えた時にfalseを返すこと.
     */
    public function testIntrospectionSuccessReturnsFalseWithExpiredAccessToken(): void
    {
        $expired = $this->createToken(TokenType::ACCESS, now()->subHour());

        $instance = $this->builder()->create(Entity::class, null, [
            'accessToken' => $expired,
        ]);

        $this->authentications = clone $this->authentications->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(
            value: $parameters['accessToken']['value'],
            type: $parameters['accessToken']['type']
        );

        $this->assertFalse($actual);
    }

    /**
     * @testdox testIntrospectionSuccessReturnsFalseWithExpiredRefreshToken introspectionメソッドで有効期限切れのリフレッシュトークンを与えた時にfalseを返すこと.
     */
    public function testIntrospectionSuccessReturnsFalseWithExpiredRefreshToken(): void
    {
        $expired = $this->createToken(TokenType::REFRESH, now()->subHour());

        $instance = $this->builder()->create(Entity::class, null, [
            'refreshToken' => $expired,
        ]);

        $this->authentications = clone $this->authentications->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(
            value: $parameters['refreshToken']['value'],
            type: $parameters['refreshToken']['type']
        );

        $this->assertFalse($actual);
    }

    /**
     * @testdox testRefreshSuccessfulReturnsRefreshedAuthentication refreshメソッドで認証を更新できること.
     */
    public function testRefreshSuccessfulReturnsRefreshedAuthentication(): void
    {
        $existence = $this->builder()->create(Entity::class, null, [
            'refreshToken' => $this->createToken(TokenType::REFRESH, now()->addHour()),
        ]);

        $this->authentications = clone $this->authentications->concat([$existence]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($existence);

        $actual = $useCase->refresh(
            value: $parameters['refreshToken']['value'],
            type: $parameters['refreshToken']['type']
        );

        $this->assertTrue($existence->identifier()->equals($actual->identifier()));
        $this->assertFalse($existence->accessToken()->equals($actual->accessToken()));
        $this->assertFalse($existence->refreshToken()->equals($actual->refreshToken()));
    }

    /**
     * @testdox testRevokeSuccess revokeメソッドで指定したトークンを破棄できること.
     */
    public function testRevokeSuccess(): void
    {
        $target = $this->authentications->random();

        [$removed, $onRemove] = $this->createRemoveHandler();

        $parameters = $this->createParametersFromEntity($target);

        $useCase = new UseCase(
            authRepository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['instances' => $this->authentications, 'onRemove' => $onRemove]
            ),
            userRepository: $this->builder()->create(UserRepository::class),
        );

        $useCase->revoke(
            value: $parameters['accessToken']['value'],
            type: $parameters['accessToken']['type']
        );

        $actual = $removed->first(
            fn (Entity $entity): bool => $entity->identifier()->equals($target->identifier())
        );

        $this->assertNotNull($actual);
        $this->assertNull($actual->accessToken());
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            authRepository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['onPersist' => $onPersisted]
            ),
            userRepository: $this->builder()->create(
                UserRepository::class,
                null,
                ['instances' => $this->users]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function createPersistUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            authRepository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['instances' => $this->authentications, 'onPersist' => $onPersisted]
            ),
            userRepository: $this->builder()->create(
                UserRepository::class,
                null,
                ['instances' => $this->users]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function assertEntity($expected, $actual): void
    {
        $this->assertInstanceOf(Entity::class, $expected);
        $this->assertInstanceOf(Entity::class, $actual);
        $this->assertTrue($expected->identifier()->equals($actual->identifier()));
        $this->assertTrue($expected->user()->equals($actual->user()));
        $this->assertTrue($expected->accessToken()->equals($actual->accessToken()));
        $this->assertTrue($expected->refreshToken()->equals($actual->refreshToken()));
    }

    /**
     * テストに使用する認証インスタンスを生成するへルパ.
     */
    private function createAuthentications(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * テストに使用するユーザーインスタンスを生成するへルパ.
     */
    private function createUsers(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(User::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * テストに使用するトークンを生成するへルパ.
     */
    private function createToken(TokenType $type, \DateTimeInterface $expiresAt): Token
    {
        return $this->builder()->create(Token::class, null, ['type' => $type, 'expiresAt' => $expiresAt]);
    }

    /**
     * エンティティからintrospectionメソッドに使用するパラメータを生成するへルパ.
     */
    private function createParametersFromEntity(Entity $entity): array
    {
        return [
            'identifier' => $entity->identifier()->value(),
            'user' => $entity->user()->value(),
            'accessToken' => [
                'type' => $entity->accessToken()->type()->name,
                'value' => $entity->accessToken()->value(),
                'expiresAt' => $entity->accessToken()->expiresAt()->toAtomString(),
            ],
            'refreshToken' => [
                'type' => $entity->refreshToken()->type()->name,
                'value' => $entity->refreshToken()->value(),
                'expiresAt' => $entity->refreshToken()->expiresAt()->toAtomString(),
            ],
        ];
    }
}
