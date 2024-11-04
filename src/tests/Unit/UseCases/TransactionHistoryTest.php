<?php

namespace Tests\Unit\UseCases;

use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\TransactionType;
use App\UseCases\Factories\CommonDomainFactory;
use App\UseCases\TransactionHistory as UseCase;
use Illuminate\Support\Enumerable;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\UseCases\PersistUseCaseTest;

/**
 * @group unit
 * @group usecases
 * @group transactionhistory
 *
 * @coversNothing
 */
class TransactionHistoryTest extends TestCase
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規の取引履歴を永続化できること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
            identifier: $parameters['identifier'],
            customer: $parameters['customer'],
            user: $parameters['user'],
            type: $parameters['type'],
            description: $parameters['description'],
            date: $parameters['date'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testPersistSuccessInCaseOnUpdate persistメソッドで既存の取引履歴を上書きして永続化できること.
     */
    public function testPersistSuccessInCaseOnUpdate(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
            identifier: $parameters['identifier'],
            customer: $parameters['customer'],
            user: $parameters['user'],
            type: $parameters['type'],
            description: $parameters['description'],
            date: $parameters['date'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testFindSuccess findメソッドで取引履歴を取得できること.
     */
    public function testFindSuccess(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccess listメソッドで取引履歴一覧を取得できること.
     */
    public function testListSuccess(): void
    {
        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list();

        $expecteds
          ->zip($actuals)
          ->eachSpread(function (Entity $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定した取引履歴を削除できること.
     */
    public function testDeleteSuccess(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                TransactionHistoryRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
            factory: new CommonDomainFactory(),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $entity) use ($target): void {
            $this->assertFalse($entity->identifier()->equals($target->identifier()));
        });
    }

    /**
     * @testdox testOfUserSuccessReturnsEntities ofCustomerメソッドで指定した顧客の取引履歴一覧を取得できること.
     */
    public function testOfUserSuccessReturnsEntities(): void
    {
        $user = $this->instances->random()->user();

        $expecteds = $this->instances
          ->filter(fn (Entity $entity): bool => $entity->user()->equals($user));

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->ofUser($user->value());

        $expecteds
          ->zip($actuals)
          ->eachSpread(function (Entity $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testOfCustomerSuccessReturnsEntities ofCustomerメソッドで指定した顧客の取引履歴一覧を取得できること.
     */
    public function testOfCustomerSuccessReturnsEntities(): void
    {
        $customer = $this->instances->random()->customer();

        $expecteds = $this->instances
          ->filter(fn (Entity $entity): bool => $entity->customer()->equals($customer));

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->ofCustomer($customer->value());

        $expecteds
          ->zip($actuals)
          ->eachSpread(function (Entity $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * {@inheritDoc}
     */
    protected function createPersistUseCase()
    {
        [$persisted, $onPersist] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                TransactionHistoryRepository::class,
                null,
                ['instances' => $this->instances, 'onPersist' => $onPersist]
            ),
            factory: new CommonDomainFactory(),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersist] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                TransactionHistoryRepository::class,
                null,
                ['onPersist' => $onPersist]
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
        $this->assertTrue($expected->customer()->equals($actual->customer()));
        $this->assertTrue($expected->user()->equals($actual->user()));
        $this->assertTrue($expected->type() === $actual->type());
        $this->assertNullOr(
            $expected->description(),
            $actual->description(),
            fn ($expected, $actual) => $this->assertTrue($expected === $actual)
        );
        $this->assertTrue($expected->date()->toAtomString() === $actual->date()->toAtomString());
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
          'customer' => $entity->customer()->value(),
          'user' => $entity->user()->value(),
          'type' => $this->convertTransactionType($entity->type()),
          'description' => $entity->description(),
          'date' => $entity->date()->toAtomString(),
        ];
    }

    /**
     * 取引履歴種別を文字列の値に変換する.
     */
    private function convertTransactionType(TransactionType $type): string
    {
        return match ($type) {
            TransactionType::MAINTENANCE => '1',
            TransactionType::CLEANING => '2',
            TransactionType::GRAVESTONE_INSTALLATION => '3',
            TransactionType::GRAVESTONE_REMOVAL => '4',
            TransactionType::GRAVESTONE_REPLACEMENT => '5',
            TransactionType::GRAVESTONE_REPAIR => '6',
            TransactionType::OTHER => '99',
        };
    }
}
