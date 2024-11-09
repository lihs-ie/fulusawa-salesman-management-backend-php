<?php

namespace Tests\Unit\UseCases;

use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\Role;
use App\UseCases\Factories\CommonDomainFactory;
use App\UseCases\User as UseCase;
use Illuminate\Support\Enumerable;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group user
 *
 * @coversNothing
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規のスケジュールを永続化できること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
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
     * @testdox testPersistSuccessInCaseOnUpdate persistメソッドで既存のスケジュールを上書きして永続化できること.
     */
    public function testPersistSuccessInCaseOnUpdate(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
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
     * @testdox testFindSuccessReturnsEntity findメソッドでスケジュール情報を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでスケジュール情報一覧を取得できること.
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
            });
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithCriteria listメソッドでスケジュール情報一覧を取得できること.
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
            });
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定したスケジュール情報を削除できること.
     */
    public function testDeleteSuccess(): void
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
        $this->assertTrue($expected->password() === $actual->password());
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
        $role = match ($entity->role()) {
            Role::ADMIN => '1',
            Role::USER => '2',
        };

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
            'role' => $role,
        ];
    }
}
