<?php

namespace Tests\Unit\Infrastructures\Schedule;

use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Infrastructures\Schedule\EloquentScheduleRepository;
use App\Infrastructures\Schedule\Models\Schedule as Record;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group schedule
 *
 * @coversNothing
 */
class EloquentScheduleRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の日報を永続化できること.
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
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の日報を更新できること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
          'identifier' => new ScheduleIdentifier($record->identifier),
          'user' => new UserIdentifier($record->user),
        ]);

        $repository = $this->createRepository();

        $repository->persist($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで日報を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new ScheduleIdentifier($record->identifier));

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで指定した条件の日報を取得できること.
     */
    public function testListSuccessReturnsAllEntitiesWithEmptyCriteria(): void
    {
        $criteria = $this->builder()->create(Criteria::class);

        $repository = $this->createRepository();

        $actuals = $repository->list($criteria);

        $this->assertSame($this->records->count(), $actuals->count());

        $actuals->each(function ($actual): void {
            $this->assertInstanceOf(Entity::class, $actual);
            $this->assertRecordProperties($actual);
        });
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithFilledCriteria listメソッドで指定した条件の日報を取得できること.
     */
    public function testListSuccessReturnsEntitiesWithFilledCriteria(): void
    {
        $record = $this->pickRecord();

        $criteria = $this->builder()->create(Criteria::class, null, [
          'filled' => true,
        ]);
        $repository = $this->createRepository();

        $expecteds = $this->records
          ->filter(
              fn (Record $record): bool => $record->status === $criteria->status()?->name
          )->pipe(
              function (Enumerable $records) use ($criteria): Enumerable {
                  $date = $criteria->date();

                  if (!\is_null($date->start())) {
                      return $records->filter(
                          fn (Record $record): bool => $record->start >= $date->start()->toAtomString() && $record->start <= $date->end()->toAtomString()
                      );
                  }

                  return $records;
              }
          )
          ->pipe(function (Enumerable $records) use ($criteria): Enumerable {
              if (!\is_null($criteria->title())) {
                  return $records->filter(
                      fn (Record $record): bool => \str_contains($record->title, $criteria->title())
                  );
              }

              return $records;
          });

        $actuals = $repository->list($criteria);

        $this->assertSame($expecteds->count(), $actuals->count());

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
     * @testdox testOfUserSuccessReturnsEntities ofUserメソッドで指定したユーザーの日報を取得できること.
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
    private function createRepository(): ScheduleRepository
    {
        return new EloquentScheduleRepository(new Record());
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('schedules', [
          'identifier' => $entity->identifier()->value(),
          'user' => $entity->user()->value(),
          'customer' => $entity->customer()?->value(),
          'title' => $entity->title(),
          'description' => $entity->description(),
          'start' => $entity->date()->start()->toDateTimeString(),
          'end' => $entity->date()->end()->toDateTimeString(),
          'status' => $entity->status()->name,
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
        $this->assertSame($record->customer, $actual->customer());
        $this->assertSame($record->title, $actual->title());
        $this->assertSame($record->start->format(DATE_ATOM), $actual->date()->start()->toAtomString());
        $this->assertSame($record->end->format(DATE_ATOM), $actual->date()->end()->toAtomString());
        $this->assertSame($record->status, $actual->status()->name);
    }
}
