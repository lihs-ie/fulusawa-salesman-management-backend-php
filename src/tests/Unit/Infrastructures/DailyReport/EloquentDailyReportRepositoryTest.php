<?php

namespace Tests\Unit\Infrastructures\DailyReport;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Infrastructures\DailyReport\EloquentDailyReportRepository;
use App\Infrastructures\DailyReport\Models\DailyReport as Record;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group dailyreport
 *
 * @coversNothing
 */
class EloquentDailyReportRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の日報を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(user: $record->user, );

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

        $expected = $this->createEntity(
            identifier: $record->identifier,
            user: $record->user,
        );

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

        $actual = $repository->find(new DailyReportIdentifier($record->identifier));

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
          'date' => $this->builder()->create(DateTimeRange::class, null, [
            'start' => CarbonImmutable::parse($record->date)->setTime(0, 0),
            'end' => CarbonImmutable::parse($record->date)->setTime(23, 59),
          ]),
          'user' => new UserIdentifier($record->user),
          'isSubmitted' => $record->is_submitted,
        ]);

        $repository = $this->createRepository();


        $expecteds = $this->records
          ->filter(
              fn (Record $record): bool => $record->date === $criteria->date()->start()->toDateString()
              && $record->user === $criteria->user()->value()
              && $record->is_submitted === $criteria->isSubmitted()
          );

        $actuals = $repository->list($criteria);

        $expecteds->zip($actuals)
          ->eachSpread(function (?Record $expected, $actual) use ($record): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertSame($record->date, $actual->date()->toDateString());
              $this->assertSame($record->user, $actual->user()->value());
              $this->assertSame($record->is_submitted, $actual->isSubmitted());
              $this->assertRecordProperties($actual);
          });
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで日報を削除できること.
     */
    public function testDeleteSuccess(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(new DailyReportIdentifier($record->identifier));

        $this->assertDatabaseMissing('daily_reports', ['identifier' => $record->identifier]);
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
    private function createRepository(): DailyReportRepository
    {
        return new EloquentDailyReportRepository(new Record());
    }

    /**
     * エンティティを生成するへルパ.
     */
    private function createEntity(string $user, string $identifier = null): Entity
    {
        return $this->builder()->create(Entity::class, null, [
          'identifier' => $this->builder()->create(
              DailyReportIdentifier::class,
              null,
              ['value' => $identifier]
          ),
          'user' => $this->builder()->create(
              UserIdentifier::class,
              null,
              ['value' => $user]
          ),
        ]);
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('daily_reports', [
          'identifier' => $entity->identifier()->value(),
          'user' => $entity->user()->value(),
          'date' => $entity->date()->toDateString(),
          'schedules' => $entity->schedules()
            ->map
            ->value()
            ->toJson(),
          'visits' => $entity->visits()
            ->map
            ->value()
            ->toJson(),
          'is_submitted' => $entity->isSubmitted(),
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
        $this->assertSame($record->date, $actual->date()->toDateString());

        $schedules = Collection::make(\json_decode($record->schedules, true));
        $schedules->zip($actual->schedules())
          ->eachSpread(function ($expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertInstanceOf(ScheduleIdentifier::class, $actual);
              $this->assertSame($expected, $actual->value());
          })
        ;

        $visits = Collection::make(\json_decode($record->visits, true));
        $visits->zip($actual->visits())
          ->eachSpread(function ($expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertNotNull($actual);
              $this->assertInstanceOf(VisitIdentifier::class, $actual);
              $this->assertSame($expected, $actual->value());
          })
        ;

        $this->assertSame($record->is_submitted, $actual->isSubmitted());
    }
}
