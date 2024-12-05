<?php

namespace Tests\Unit\Infrastructures\TransactionHistory;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\ConflictException;
use App\Infrastructures\TransactionHistory\EloquentTransactionHistoryRepository;
use App\Infrastructures\TransactionHistory\Models\TransactionHistory as Record;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group transactionhistory
 *
 * @coversNothing
 *
 * @internal
 */
class EloquentTransactionHistoryRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testAddSuccessPersistEntity addメソッドで新規の取引履歴を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'user' => $this->builder()->create(UserIdentifier::class, null, [
                'value' => $record->user,
            ]),
            'customer' => $this->builder()->create(CustomerIdentifier::class, null, [
                'value' => $record->customer,
            ]),
        ]);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドで重複する識別子の取引履歴を永続化しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(TransactionHistoryIdentifier::class, null, [
                'value' => $record->identifier,
            ]),
            'user' => $this->builder()->create(UserIdentifier::class, null, [
                'value' => $record->user,
            ]),
            'customer' => $this->builder()->create(CustomerIdentifier::class, null, [
                'value' => $record->customer,
            ]),
        ]);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで既存の取引履歴を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(TransactionHistoryIdentifier::class, null, [
                'value' => $record->identifier,
            ]),
            'user' => $this->builder()->create(UserIdentifier::class, null, [
                'value' => $record->user,
            ]),
            'customer' => $this->builder()->create(CustomerIdentifier::class, null, [
                'value' => $record->customer,
            ]),
        ]);

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しない取引履歴を更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'user' => $this->builder()->create(UserIdentifier::class, null, [
                'value' => $record->user,
            ]),
            'customer' => $this->builder()->create(CustomerIdentifier::class, null, [
                'value' => $record->customer,
            ]),
        ]);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->update($entity);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで取引履歴を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new TransactionHistoryIdentifier($record->identifier));

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しない取引履歴を取得しようとすると例外が発生すること.
     */
    public function testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $missing = $this->builder()->create(TransactionHistoryIdentifier::class);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->find($missing);
    }

    /**
     * @testdox testListSuccessReturnsAllEntities listメソッドで取引履歴を取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsAllEntities(\Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        $repository = $this->createRepository();

        $actuals = $repository->list($criteria);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, ?Entity $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertRecordProperties($actual);
            })
        ;
    }

    /**
     * 検索条件を提供するプロパイダ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class)];

        yield 'customer' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
            'customer' => $self->builder()->create(CustomerIdentifier::class, null, [
                'value' => $self->records->random()->customer,
            ]),
        ])];

        yield 'user' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
            'user' => $self->builder()->create(UserIdentifier::class, null, [
                'value' => $self->records->random()->user,
            ]),
        ])];

        yield 'sort' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
            'sort' => $self->builder()->create(Sort::class),
        ])];

        yield 'fulfilled' => [function (self $self): Criteria {
            $record = $self->records->random();

            return $self->builder()->create(Criteria::class, null, [
                'customer' => $self->builder()->create(CustomerIdentifier::class, null, [
                    'value' => $record->customer,
                ]),
                'user' => $self->builder()->create(UserIdentifier::class, null, [
                    'value' => $record->user,
                ]),
                'sort' => $self->builder()->create(Sort::class),
            ]);
        }];
    }

    /**
     * {@inheritDoc}
     */
    protected function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * リポジトリを生成するへルパ.
     */
    private function createRepository(): TransactionHistoryRepository
    {
        return new EloquentTransactionHistoryRepository(new Record());
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('transaction_histories', [
            'identifier' => $entity->identifier()->value(),
            'customer' => $entity->customer()->value(),
            'user' => $entity->user()->value(),
            'type' => $entity->type()->name,
            'description' => $entity->description(),
            'date' => $entity->date()->toAtomString(),
        ]);
    }

    /**
     * レコードとエンティティのプロパティを比較する.
     */
    private function assertRecordProperties(Entity $actual): void
    {
        $record = $this->records->first(
            fn (Record $record): bool => $record->identifier === $actual->identifier()->value()
        );

        $this->assertNotNull($record);
        $this->assertSame($record->identifier, $actual->identifier()->value());
        $this->assertSame($record->customer, $actual->customer()->value());
        $this->assertSame($record->user, $actual->user()->value());
        $this->assertSame($record->type, $actual->type()->name);
        $this->assertSame($record->description, $actual->description());
        $this->assertSame($record->date, $actual->date()->toDateString());
    }

    /**
     * listメソッドの期待値を生成する.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->records
            ->when(
                !\is_null($criteria->user()),
                fn (Enumerable $records) => $records
                    ->where('user', $criteria->user()->value())
            )
            ->when(
                !\is_null($criteria->customer()),
                fn (Enumerable $records) => $records
                    ->where('customer', $criteria->customer()->value())
            )
            ->when(!\is_null($criteria->sort()), fn (Enumerable $records) => match ($criteria->sort()) {
                Sort::CREATED_AT_ASC => $records->sortBy('created_at'),
                Sort::CREATED_AT_DESC => $records->sortByDesc('created_at'),
                Sort::UPDATED_AT_ASC => $records->sortBy('updated_at'),
                Sort::UPDATED_AT_DESC => $records->sortByDesc('updated_at'),
            })
            ->values()
        ;
    }
}
