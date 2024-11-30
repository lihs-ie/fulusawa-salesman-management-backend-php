<?php

namespace Tests\Unit\Infrastructures\Cemetery;

use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Infrastructures\Cemetery\EloquentCemeteryRepository;
use App\Infrastructures\Cemetery\Models\Cemetery as Record;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group cemetery
 *
 * @coversNothing
 */
class EloquentCemeteryRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の墓地情報を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(customer: $record->customer);

        $repository = $this->createRepository();

        $repository->persist($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の墓地情報を更新できること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        $record = $this->pickRecord();

        $expected = $this->createEntity(
            identifier: $record->identifier,
            customer: $record->customer,
        );

        $repository = $this->createRepository();

        $repository->persist($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで墓地情報を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new CemeteryIdentifier($record->identifier));

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testOfCustomerSuccessReturnsEntities ofCustomerメソッドで指定した顧客の墓地情報のリストを取得できること.
     */
    public function testOfCustomerSuccessReturnsEntities(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actuals = $repository->ofCustomer(new CustomerIdentifier($record->customer));

        $actuals->each(function (Entity $actual) use ($record): void {
            $this->assertSame($record->customer, $actual->customer()->value());
            $this->assertRecordProperties($actual);
        });
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで墓地情報のリストを取得できること.
     */
    public function testListSuccessReturnsEntities(): void
    {
        $repository = $this->createRepository();

        $record = $this->pickRecord();

        $criteria = new Criteria(
            customer: new CustomerIdentifier($record->customer),
        );

        $expecteds = $this->records
            ->filter(fn (Record $record): bool => $record->customer === $criteria->customer->value());

        $actuals = $repository->list($criteria);

        $this->assertSame($expecteds->count(), $actuals->count());

        $actuals->each(function (Entity $actual): void {
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
    private function createRepository(): CemeteryRepository
    {
        return new EloquentCemeteryRepository(new Record());
    }

    /**
     * エンティティを生成するへルパ.
     */
    private function createEntity(string $customer, string $identifier = null): Entity
    {
        return $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                CemeteryIdentifier::class,
                null,
                ['value' => $identifier]
            ),
            'customer' => $this->builder()->create(
                CustomerIdentifier::class,
                null,
                ['value' => $customer]
            ),
        ]);
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('cemeteries', [
            'identifier' => $entity->identifier()->value(),
            'customer' => $entity->customer()->value(),
            'name' => $entity->name(),
            'type' => $entity->type()->name,
            'construction' => $entity->construction()->toAtomString(),
            'in_house' => $entity->inHouse(),
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
        $this->assertSame($record->name, $actual->name());
        $this->assertSame($record->type, $actual->type()->name);
        $this->assertSame(
            $record->construction->format(DATE_ATOM),
            $actual->construction()->toAtomString()
        );
        $this->assertSame($record->in_house, $actual->inHouse());
    }
}
