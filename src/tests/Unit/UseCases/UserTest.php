<?php

namespace Tests\Unit\UseCases;

use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\ConflictException;
use App\UseCases\User as UseCase;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group user
 *
 * @coversNothing
 *
 * @internal
 */
class UserTest extends TestCase
{
    use DependencyBuildable;
    use NullableValueComparable;
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規のユーザーを永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            email: $parameters['email'],
            password: $parameters['password'],
            role: $parameters['role'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドで既存のユーザーを追加しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $instance = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            email: $parameters['email'],
            password: $parameters['password'],
            role: $parameters['role'],
        );
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateEmail addメソッドで既存のメールアドレスを追加しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateEmail(): void
    {
        $instance = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $this->builder()->create(UserIdentifier::class)->value(),
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            email: $parameters['email'],
            password: $parameters['password'],
            role: $parameters['role'],
        );
    }

    /**
     * @testdox testUpdatePersistEntity updateソッドで既存のユーザーを上書きして永続化できること.
     */
    public function testUpdatePersistEntity(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->update(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            email: $parameters['email'],
            password: $parameters['password'],
            role: $parameters['role'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで既存のユーザーを追加しようとすると例外が発生すること.
     */
    public function testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $instance = $this->builder()->create(Entity::class);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $this->expectException(\OutOfBoundsException::class);

        $useCase->update(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            email: $parameters['email'],
            password: $parameters['password'],
            role: $parameters['role'],
        );
    }

    /**
     * @testdox testUpdateFailureThrowsConflictExceptionWithDuplicateEmail updateメソッドで既存のメールアドレスを追加しようとすると例外が発生すること.
     */
    public function testUpdateFailureThrowsConflictExceptionWithDuplicateEmail(): void
    {
        $target = $this->instances->random();

        $duplicate = $this->instances->first(
            fn (Entity $instance) => !$instance->identifier()->equals($target->identifier())
        );

        $next = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier(), 'email' => $duplicate->email()]
        );

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($next);

        $this->expectException(ConflictException::class);

        $useCase->update(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            email: $parameters['email'],
            password: $parameters['password'],
            role: $parameters['role'],
        );
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドでユーザーを取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでユーザー一覧を取得できること.
     */
    public function testListSuccessReturnsEntitiesWithEmptyCriteria(): void
    {
        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list([]);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            })
        ;
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithCriteria listメソッドでユーザー一覧を取得できること.
     */
    public function testListSuccessReturnsEntitiesWithCriteria(): void
    {
        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list();

        $this->assertSame($expecteds->count(), $actuals->count());

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            })
        ;
    }

    /**
     * @testdox testDeleteSuccessRemoveEntity deleteメソッドで指定したユーザーを削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                UserRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $instance) use ($target): void {
            $this->assertFalse($instance->identifier()->equals($target->identifier()));
        });
    }

    /**
     * @testdox testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しないユーザーを削除しようとすると例外が発生すること.
     */
    public function testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $useCase = new UseCase(
            repository: $this->builder()->create(
                UserRepository::class,
                null,
                ['instances' => $this->instances]
            ),
        );

        $this->expectException(\OutOfBoundsException::class);

        $useCase->delete($this->builder()->create(UserIdentifier::class)->value());
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                UserRepository::class,
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
                UserRepository::class,
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
        $this->assertTrue($expected->firstName() === $actual->firstName());
        $this->assertTrue($expected->lastName() === $actual->lastName());
        $this->assertTrue($expected->address()->equals($actual->address()));
        $this->assertTrue($expected->phone()->equals($actual->phone()));
        $this->assertTrue($expected->email()->equals($actual->email()));
        Hash::isHashed($actual->password())
            ? $this->assertTrue(Hash::check($expected->password(), $actual->password()))
            : $this->assertTrue($expected->password() === $actual->password());
        $this->assertTrue($expected->role() === $actual->role());
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * エンティティからpersistメソッドに使用するパラメータを生成するへルパ.
     */
    private function createParametersFromEntity(Entity $entity): array
    {
        return [
            'identifier' => $entity->identifier()->value(),
            'name' => [
                'first' => $entity->firstName(),
                'last' => $entity->lastName(),
            ],
            'address' => [
                'postalCode' => [
                    'first' => $entity->address()->postalCode()->first(),
                    'second' => $entity->address()->postalCode()->second(),
                ],
                'prefecture' => $entity->address()->prefecture()->value,
                'city' => $entity->address()->city(),
                'street' => $entity->address()->street(),
                'building' => $entity->address()->building(),
            ],
            'phone' => [
                'areaCode' => $entity->phone()->areaCode(),
                'localCode' => $entity->phone()->localCode(),
                'subscriberNumber' => $entity->phone()->subscriberNumber(),
            ],
            'email' => $entity->email()->value(),
            'password' => $entity->password(),
            'role' => $entity->role()->name,
        ];
    }
}
