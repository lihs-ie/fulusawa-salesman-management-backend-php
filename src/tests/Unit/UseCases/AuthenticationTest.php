<?php

namespace Tests\Unit\UseCases;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\Token;
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
        $mail = $this->builder()->create(MailAddress::class);
        $password = 'test-password';

        $expected = $this->builder()->create(Entity::class);

        [$useCase] = $this->createEmptyPersistedUseCase();

        $actual = $useCase->persist(
            identifier: $expected->identifier()->value(),
            mail: $mail->value(),
            password: $password
        );

        $this->assertTrue($expected->identifier()->equals($actual->identifier()));
    }

    /**
     * @testdox testIntrospectionSuccessWithValidAuthentication introspectionメソッドで有効な認証を与えた時にtrueを返すこと.
     */
    public function testIntrospectionSuccessWithValidAuthentication(): void
    {
        $instance = $this->builder()->create(Entity::class, null, [
          'accessToken' => $this->builder()->create(Token::class, null, ['expiresAt' => now()->addHour()]),
          'refreshToken' => $this->builder()->create(Token::class, null, ['expiresAt' => now()->addHour()]),
        ]);

        $this->instances = clone $this->instances->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(
            identifier: $parameters['identifier'],
            accessToken: $parameters['accessToken'],
            refreshToken: $parameters['refreshToken']
        );

        $this->assertTrue($actual->get('accessToken')['active']);
        $this->assertTrue($actual->get('refreshToken')['active']);
    }

    /**
     * @testdox testIntrospectionSuccessWithExpiredAccessToken introspectionメソッドで有効期限切れのアクセストークンを与えた時にfalseを返すこと.
     */
    public function testIntrospectionSuccessWithExpiredAuthentication(): void
    {
        $instance = $this->builder()->create(Entity::class, null, [
          'accessToken' => $this->builder()->create(Token::class, null, ['expiresAt' => now()->subHour()]),
          'refreshToken' => $this->builder()->create(Token::class, null, ['expiresAt' => now()->addHour()]),
        ]);

        $this->instances = clone $this->instances->concat([$instance]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $actual = $useCase->introspection(
            identifier: $parameters['identifier'],
            accessToken: $parameters['accessToken'],
            refreshToken: $parameters['refreshToken']
        );

        $this->assertFalse($actual->get('accessToken')['active']);
        $this->assertTrue($actual->get('refreshToken')['active']);
    }

    /**
     * @testdox testRefreshSuccess refreshメソッドで認証を更新できること.
     */
    public function testRefreshSuccess(): void
    {
        $existence = $this->builder()->create(Entity::class, null, [
          'refreshToken' => $this->builder()->create(Token::class, null, ['expiresAt' => now()->addHour()]),
        ]);

        $this->instances = clone $this->instances->concat([$existence]);

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->refresh($existence->identifier()->value());

        $this->assertTrue($existence->identifier()->equals($actual->identifier()));
        $this->assertFalse($existence->accessToken()->equals($actual->accessToken()));
        $this->assertFalse($existence->refreshToken()->equals($actual->refreshToken()));
    }

    /**
     * @testdox testRevokeSuccess revokeメソッドで認証を削除できること.
     */
    public function testRevokeSuccess(): void
    {
        $target = $this->instances->random();

        [$removed, $onRemove] = $this->createRemoveHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                AuthenticationRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
            factory: new CommonDomainFactory(),
        );

        $useCase->revoke($target->identifier()->value());

        $removed->each(function (Entity $instance) use ($target): void {
            $this->assertFalse($instance->identifier()->equals($target->identifier()));
        });
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
            factory: new CommonDomainFactory(),
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
            factory: new CommonDomainFactory(),
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
     * エンティティからintrospectionメソッドに使用するパラメータを生成するへルパ.
     */
    private function createParametersFromEntity(Entity $entity): array
    {
        return [
          'identifier' => $entity->identifier()->value(),
          'accessToken' => [
            'value' => $entity->accessToken()->value(),
            'expiresAt' => $entity->accessToken()->expiresAt()->toAtomString(),
          ],
          'refreshToken' => [
            'value' => $entity->refreshToken()->value(),
            'expiresAt' => $entity->refreshToken()->expiresAt()->toAtomString(),
          ],
        ];
    }
}
