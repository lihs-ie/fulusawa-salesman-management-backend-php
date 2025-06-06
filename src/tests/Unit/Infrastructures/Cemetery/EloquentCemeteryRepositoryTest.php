<?php

namespace Tests\Unit\Infrastructures\Cemetery;

use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Exceptions\ConflictException;
use App\Infrastructures\Cemetery\EloquentCemeteryRepository;
use App\Infrastructures\Cemetery\Models\Cemetery as Record;
use Illuminate\Support\Enumerable;
use Ramsey\Uuid\Uuid;
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規の墓地情報を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(customer: $record->customer);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddThrowsConflictExceptionDuplicateIdentifier addメソッドで重複した識別子の墓地情報を与えたときConflictExceptionが投げられること.
     */
    public function testAddThrowsConflictExceptionDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $expected = $this->createEntity(
            identifier: $record->identifier,
        );

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($expected);
    }

    /**
     * @testdox testAddThrowsUnexpectedValueExceptionWithMissingCustomer addメソッドに存在しない顧客識別子を含む墓地情報を与えたときUnexpectedValueExceptionが投げられること.
     */
    public function testAddThrowsUnexpectedValueExceptionWithMissingCustomer(): void
    {
        $expected = $this->createEntity();

        $repository = $this->createRepository();

        $this->expectException(\UnexpectedValueException::class);

        $repository->add($expected);
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで墓地情報を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(
            identifier: $record->identifier,
            customer: $record->customer
        );

        $repository = $this->createRepository();

        $repository->update($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testUpdateThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しない墓地情報を指定したときOutOfBoundsExceptionが投げられること.
     */
    public function testUpdateThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $expected = $this->createEntity();

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->update($expected);
    }

    /**
     * @testdox testUpdateThrowsUnexpectedValueExceptionWithMissingCustomer updateメソッドに存在しない顧客識別子を含む墓地情報を与えたときUnexpectedValueExceptionが投げられること.
     */
    public function testUpdateThrowsUnexpectedValueExceptionWithMissingCustomer(): void
    {
        $record = $this->pickRecord();

        $expected = $this->createEntity(
            identifier: $record->identifier,
        );

        $repository = $this->createRepository();

        $this->expectException(\UnexpectedValueException::class);

        $repository->update($expected);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで指定した識別子の墓地情報を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new CemeteryIdentifier($record->identifier));

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testFindThrowsOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しない墓地情報識別子を指定したときOutOfBoundsExceptionが投げられること.
     */
    public function testFindThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $identifier = $this->builder()->create(CemeteryIdentifier::class);

        $this->expectException(\OutOfBoundsException::class);

        $repository->find($identifier);
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
     * @testdox testDeleteSuccessRemoveEntity deleteメソッドで墓地情報を削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(new CemeteryIdentifier($record->identifier));

        $this->assertDatabaseMissing('cemeteries', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteThrowsOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しない墓地情報識別子を指定したときOutOfBoundsExceptionが投げられること.
     */
    public function testDeleteThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->delete(new CemeteryIdentifier(Uuid::uuid7()->toString()));
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
    private function createEntity(string $customer = null, string $identifier = null): Entity
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
