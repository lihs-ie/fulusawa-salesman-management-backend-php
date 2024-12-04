<?php

namespace Tests\Unit\UseCases;

use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Exceptions\ConflictException;
use App\UseCases\TransactionHistory as UseCase;
use Illuminate\Support\Enumerable;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

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
     * @testdox testAddSuccessPersistEntity addメソッドで取引履歴を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
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
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドで既存の取引履歴と同じ識別子を持つ取引履歴を追加しようとしたとき例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $target = $this->instances->random();

        $parameters = $this->createParametersFromEntity($target);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $parameters['identifier'],
            customer: $parameters['customer'],
            user: $parameters['user'],
            type: $parameters['type'],
            description: $parameters['description'],
            date: $parameters['date'],
        );
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで取引履歴を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->update(
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
     * @testdox testFindSuccessReturnsEntity findメソッドで取引履歴を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで取引履歴一覧を取得できること.
     * 
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntities(\Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list($this->deflateCriteria($criteria));

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [
            fn(self $self): Criteria => $self->builder()->create(Criteria::class)
        ];

        yield 'user' => [
            fn(self $self): Criteria => $self->builder()->create(
                Criteria::class,
                null,
                ['user' => $self->instances->random()->user()]
            )
        ];

        yield 'customer' => [
            fn(self $self): Criteria => $self->builder()->create(
                Criteria::class,
                null,
                ['customer' => $self->instances->random()->customer()]
            )
        ];

        yield 'sort' => [
            fn(self $self): Criteria => $self->builder()->create(
                Criteria::class,
                null,
                ['sort' => $self->builder()->create(Sort::class)]
            )
        ];

        yield 'fulfilled' => [
            function (self $self): Criteria {
                $entity = $self->instances->random();

                return $self->builder()->create(
                    Criteria::class,
                    null,
                    [
                        'user' => $entity->user(),
                        'customer' => $entity->customer(),
                        'sort' => $self->builder()->create(Sort::class),
                    ]
                );
            }
        ];
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定した取引履歴を削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                TransactionHistoryRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $entity) use ($target): void {
            $this->assertFalse($entity->identifier()->equals($target->identifier()));
        });
    }

    /**
     * @testdox testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しない取引履歴を削除しようとしたとき例外が発生すること.
     */
    public function testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(TransactionHistoryIdentifier::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->delete($identifier->value());
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
            fn($expected, $actual) => $this->assertTrue($expected === $actual)
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
            'type' => $entity->type()->name,
            'description' => $entity->description(),
            'date' => $entity->date()->toAtomString(),
        ];
    }

    /**
     * listメソッドの期待値を生成するへルパ.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->instances
            ->when(!\is_null($criteria->user()), fn(Enumerable $instances) => $instances->filter(
                fn(Entity $entity): bool => $criteria->user()->equals($entity->user())
            ))
            ->when(!\is_null($criteria->customer()), fn(Enumerable $instances) => $instances->filter(
                fn(Entity $entity): bool => $criteria->customer()->equals($entity->customer())
            ))
            ->when(!\is_null($criteria->sort()), fn(Enumerable $instances) => match ($criteria->sort()) {
                Sort::CREATED_AT_ASC => $instances->sortBy(fn(Entity $entity): \DateTimeInterface => $entity->date()),
                Sort::CREATED_AT_DESC => $instances->sortByDesc(fn(Entity $entity): \DateTimeInterface => $entity->date()),
                Sort::UPDATED_AT_ASC => $instances->sortBy(fn(Entity $entity): \DateTimeInterface => $entity->date()),
                Sort::UPDATED_AT_DESC => $instances->sortByDesc(fn(Entity $entity): \DateTimeInterface => $entity->date()),
            });
    }

    /**
     * 検索条件の配列を生成するへルパ.
     */
    private function deflateCriteria(Criteria $criteria): array
    {
        return [
            'user' => $criteria->user()?->value(),
            'customer' => $criteria->customer()?->value(),
            'sort' => $criteria->sort()?->name,
        ];
    }
}
