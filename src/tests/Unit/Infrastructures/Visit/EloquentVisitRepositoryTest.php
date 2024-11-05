<?php

namespace Tests\Unit\Infrastructures\Visit;

use App\Domains\Visit\VisitRepository;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Visit\EloquentVisitRepository;
use App\Infrastructures\Visit\Models\Visit as Record;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group visit
 *
 * @coversNothing
 */
class EloquentVisitRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の訪問を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
          'user' => new UserIdentifier($record->user),
        ]);

        $repository = $this->createRepository();

        $repository->persist($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の訪問を更新できること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
          'identifier' => new VisitIdentifier($record->identifier),
          'user' => new UserIdentifier($record->user),
        ]);

        $repository = $this->createRepository();

        $repository->persist($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで訪問を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new VisitIdentifier($record->identifier));

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testListSuccessReturnsAllEntities listメソッドで全ての訪問を取得できること.
     */
    public function testListSuccessReturnsAllEntities(): void
    {
        $repository = $this->createRepository();

        $actuals = $repository->list();

        $this->assertSame($this->records->count(), $actuals->count());

        $actuals->each(function ($actual): void {
            $this->assertInstanceOf(Entity::class, $actual);
            $this->assertRecordProperties($actual);
        });
    }

    /**
     * @testdox testOfUserSuccessReturnsEntities ofUserメソッドで指定したユーザーの訪問を取得できること.
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
     * {@inheritDoc}
     */
    protected function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * リポジトリを生成するへルパ.
     */
    private function createRepository(): VisitRepository
    {
        return new EloquentVisitRepository(new Record());
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('visits', [
          'identifier' => $entity->identifier()->value(),
          'user' => $entity->user()->value(),
          'visited_at' => $entity->visitedAt()->toAtomString(),
          'phone_area_code' => $entity->phone()?->areaCode(),
          'phone_local_code' => $entity->phone()?->localCode(),
          'phone_subscriber_number' => $entity->phone()?->subscriberNumber(),
          'postal_code_first' => $entity->address()->postalCode()->first(),
          'postal_code_second' => $entity->address()->postalCode()->second(),
          'prefecture' => $entity->address()->prefecture()->value,
          'city' => $entity->address()->city(),
          'street' => $entity->address()->street(),
          'building' => $entity->address()->building(),
          'has_graveyard' => $entity->hasGraveyard(),
          'note' => $entity->note(),
          'result' => $entity->result()->name,
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
        $this->assertSame($record->user, $actual->user()->value());
        $this->assertSame($record->phone_area_code, $actual->phone()?->areaCode());
        $this->assertSame($record->phone_local_code, $actual->phone()?->localCode());
        $this->assertSame($record->phone_subscriber_number, $actual->phone()?->subscriberNumber());
        $this->assertSame($record->postal_code_first, $actual->address()->postalCode()->first());
        $this->assertSame($record->postal_code_second, $actual->address()->postalCode()->second());
        $this->assertSame($record->prefecture, $actual->address()->prefecture()->value);
        $this->assertSame($record->city, $actual->address()->city());
        $this->assertSame($record->street, $actual->address()->street());
        $this->assertSame($record->building, $actual->address()->building());
        $this->assertSame($record->has_graveyard, $actual->hasGraveyard());
        $this->assertSame($record->note, $actual->note());
        $this->assertSame($record->result, $actual->result()->name);
    }
}
