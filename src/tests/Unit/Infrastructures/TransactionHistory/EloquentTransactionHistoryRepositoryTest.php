<?php

namespace Tests\Unit\Infrastructures\TransactionHistory;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\TransactionHistoryRepository;
use App\Domains\TransactionHistory\Entities\TransactionHistory as Entity;
use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
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
 */
class EloquentTransactionHistoryRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の取引履歴を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'user' => new UserIdentifier($record->user),
            'customer' => new CustomerIdentifier($record->customer),
        ]);

        $repository = $this->createRepository();

        $repository->persist($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の取引履歴を更新できること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => new TransactionHistoryIdentifier($record->identifier),
            'user' => new UserIdentifier($record->user),
            'customer' => new CustomerIdentifier($record->customer),
        ]);

        $repository = $this->createRepository();

        $repository->persist($expected);

        $this->assertPersistedRecord($expected);
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
     * @testdox testListSuccessReturnsAllEntities listメソッドで全ての取引履歴を取得できること.
     */
    public function testListSuccessReturnsAllEntities(): void
    {
        $repository = $this->createRepository();

        $actuals = $repository->list();

        $this->assertInstanceOf(Enumerable::class, $actuals);
        $this->assertSame($this->records->count(), $actuals->count());

        $actuals->each(function ($actual): void {
            $this->assertInstanceOf(Entity::class, $actual);
            $this->assertRecordProperties($actual);
        });
    }

    /**
     * @testdox testOfUserSuccessReturnsEntities ofUserメソッドで指定したユーザーの取引履歴を取得できること.
     */
    public function testOfUserSuccessReturnsEntities(): void
    {
        $target = $this->pickRecord();

        $expecteds = $this->records
            ->filter(fn (Record $record): bool => $record->user === $target->user);

        $repository = $this->createRepository();

        $actuals = $repository->ofUser(new UserIdentifier($target->user));

        $this->assertInstanceOf(Enumerable::class, $actuals);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertRecordProperties($actual);
            });
    }

    /**
     * @testdox testOfCustomerSuccessReturnsEntities ofCustomerメソッドで指定した顧客の取引履歴を取得できること.
     */
    public function testOfCustomerSuccessReturnsEntities(): void
    {
        $target = $this->pickRecord();

        $expecteds = $this->records
            ->filter(fn (Record $record): bool => $record->customer === $target->customer);

        $repository = $this->createRepository();

        $actuals = $repository->ofCustomer(new CustomerIdentifier($target->customer));

        $this->assertInstanceOf(Enumerable::class, $actuals);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertRecordProperties($actual);
            });
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
}
