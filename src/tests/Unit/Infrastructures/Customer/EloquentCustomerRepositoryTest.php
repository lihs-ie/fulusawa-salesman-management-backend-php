<?php

namespace Tests\Unit\Infrastructures\Customer;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Infrastructures\Customer\EloquentCustomerRepository;
use App\Infrastructures\Customer\Models\Customer as Record;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group customer
 *
 * @coversNothing
 */
class EloquentCustomerRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の顧客を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $entity = $this->builder()->create(Entity::class, null, [
          'cemeteries' => (bool) \mt_rand(0, 1) ?
            null : $this->builder()->createList(CemeteryIdentifier::class, \mt_rand(1, 3)),
          'transactionHistories' => (bool) \mt_rand(0, 1) ?
            null : $this->builder()->createList(TransactionHistoryIdentifier::class, \mt_rand(1, 3)),
        ]);

        $repository = $this->createRepository();

        $repository->persist($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の顧客を更新できること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
          'identifier' => new CustomerIdentifier($record->identifier)
        ]);

        $repository = $this->createRepository();

        $repository->persist($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testFindSuccess ユーザーを取得できること.
     */
    public function testFindSuccessReturnsInstance(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new CustomerIdentifier($record->identifier));

        $this->assertPropertyOf($actual);
    }

    /**
     * @testdox testListSuccessReturnsEntities 顧客一覧を取得できること.
     */
    public function testListSuccessReturnsEntities(): void
    {
        $repository = $this->createRepository();

        $actual = $repository->list();

        $actual->each(function (Entity $entity): void {
            $this->assertPropertyOf($entity);
        });
    }

    /**
     * @testdox testDeleteSuccess 顧客を削除できること.
     */
    public function testDeleteSuccess(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(new CustomerIdentifier($record->identifier));

        $this->assertDatabaseMissing('customers', ['identifier' => $record->identifier]);
    }

    /**
     * {@inheritDoc}
     */
    protected function createRecords(): Enumerable
    {
        return $this->factory(Record::class)
          ->createMany(\mt_rand(5, 10));
    }

    /**
     * リポジトリを生成するへルパ.
     */
    private function createRepository(): CustomerRepository
    {
        return new EloquentCustomerRepository(new Record());
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $cemeteries = $entity->cemeteries()
          ->map(
              fn (CemeteryIdentifier $cemetery): string => $cemetery->value()
          )
          ->all();

        $transactionHistories = $entity->transactionHistories()
          ->map(
              fn (TransactionHistoryIdentifier $transactionHistory): string => $transactionHistory->value()
          )
          ->all();

        $this->assertDatabaseHas('customers', [
          'identifier' => $entity->identifier()->value(),
          'first_name' => $entity->firstName(),
          'last_name' => $entity->lastName(),
          'postal_code_first' => $entity->address()->postalCode()->first(),
          'postal_code_second' => $entity->address()->postalCode()->second(),
          'prefecture' => $entity->address()->prefecture()->value,
          'city' => $entity->address()->city(),
          'street' => $entity->address()->street(),
          'building' => $entity->address()->building(),
          'phone_area_code' => $entity->phone()->areaCode(),
          'phone_local_code' => $entity->phone()->localCode(),
          'phone_subscriber_number' => $entity->phone()->subscriberNumber(),
          'cemeteries' => \json_encode($cemeteries),
          'transaction_histories' => \json_encode($transactionHistories),
        ]);
    }

    /**
     * レコードのプロパティを比較する.
     */
    private function assertPropertyOf(Entity $actual): void
    {
        $record = $this->records->first(
            fn (Record $record): bool => $record->identifier === $actual->identifier()->value()
        );

        $this->assertNotNull($record);
        $this->assertSame($record->first_name, $actual->firstName());
        $this->assertSame($record->last_name, $actual->lastName());
        $this->assertSame($record->phone_area_code, $actual->phone()->areaCode());
        $this->assertSame($record->phone_local_code, $actual->phone()->localCode());
        $this->assertSame($record->phone_subscriber_number, $actual->phone()->subscriberNumber());
        $this->assertSame($record->postal_code_first, $actual->address()->postalCode()->first());
        $this->assertSame($record->postal_code_second, $actual->address()->postalCode()->second());
        $this->assertSame($record->prefecture, $actual->address()->prefecture()->value);
        $this->assertSame($record->city, $actual->address()->city());
        $this->assertSame($record->street, $actual->address()->street());
        $this->assertSame($record->building, $actual->address()->building());

        $expectedCemeteries = json_decode($record->cemeteries, true);
        $this->assertSame(count($expectedCemeteries), $actual->cemeteries()->count());
        Collection::make($expectedCemeteries)
          ->zip($actual->cemeteries())
          ->eachSpread(function ($expected, $actual): void {
              $this->assertInstanceOf(CemeteryIdentifier::class, $actual);
              $this->assertSame($expected, $actual->value());
          });

        $expectedHistories = json_decode($record->transaction_histories, true);
        $this->assertSame(count($expectedHistories), $actual->transactionHistories()->count());
        Collection::make($expectedHistories)
          ->zip($actual->transactionHistories())
          ->eachSpread(function ($expected, $actual): void {
              $this->assertInstanceOf(TransactionHistoryIdentifier::class, $actual);
              $this->assertSame($expected, $actual->value());
          });
    }
}
