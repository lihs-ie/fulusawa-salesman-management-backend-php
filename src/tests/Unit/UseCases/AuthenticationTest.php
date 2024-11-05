<?php

namespace Tests\Unit\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\Common\ValueObjects\MailAddress;
use App\UseCases\Authentication as UseCase;
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group authentication
 *
 * @coversNothing
 */
class AuthenticationTest extends TestCase
{
    use DependencyBuildable;
    use PersistUseCaseTest;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable $instances;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->instances = clone $this->createInstances();
    }

    /**
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規の認証情報を永続化すること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $email = $this->builder()->create(MailAddress::class);
        $password = 'test-password';

        $expected = $this->builder()->create(Entity::class);

        [$useCase] = $this->createEmptyPersistedUseCase();

        $actual = $useCase->persist(
            identifier: $expected->identifier()->value(),
            email: $email->value(),
            password: $password
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

        $this->instances = clone $this->instances->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(token: $parameters['accessToken']);

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

        $this->instances = clone $this->instances->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(token: $parameters['refreshToken']);

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

        $this->instances = clone $this->instances->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(token: $parameters['accessToken']);

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

        $this->instances = clone $this->instances->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(token: $parameters['refreshToken']);

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

        $this->instances = clone $this->instances->concat([$existence]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($existence);

        $actual = $useCase->refresh($parameters['refreshToken']);

        $this->assertTrue($existence->identifier()->equals($actual->identifier()));
        $this->assertFalse($existence->accessToken()->equals($actual->accessToken()));
        $this->assertFalse($existence->refreshToken()->equals($actual->refreshToken()));
    }

    /**
     * @testdox testRevokeSuccess revokeメソッドで指定したトークンを破棄できること.
     */
    public function testRevokeSuccess(): void
    {
        $target = $this->instances->random();

        [$removed, $onRemove] = $this->createRemoveHandler();

        $parameters = $this->createParametersFromEntity($target);

        $useCase = new UseCase(
            repository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
        );

        $useCase->revoke($parameters['accessToken']);

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
            repository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['onPersist' => $onPersisted]
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
            repository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['instances' => $this->instances, 'onPersist' => $onPersisted]
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
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10), $overrides);
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
