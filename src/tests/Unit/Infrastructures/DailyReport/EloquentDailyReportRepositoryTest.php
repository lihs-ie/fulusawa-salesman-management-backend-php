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
use App\Exceptions\ConflictException;
use App\Infrastructures\DailyReport\EloquentDailyReportRepository;
use App\Infrastructures\DailyReport\Models\DailyReport as Record;
use Carbon\CarbonImmutable;
use Closure;
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規の日報を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(user: $record->user);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddThrowsConflictExceptionWithDuplicateIdentifier addメソッドで既に存在する日報を追加しようとすると例外が発生すること.
     */
    public function testAddThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(user: $record->user, identifier: $record->identifier);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで日報を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $expected = $this->createEntity(
            identifier: $record->identifier,
            user: $record->user,
        );

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateThrowsConflictExceptionWithMissingIdentifier updateメソッドで存在しない日報を更新しようとすると例外が発生すること.
     */
    public function testUpdateThrowsConflictExceptionWithMissingIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->createEntity(user: $record->user);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->update($entity);
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
     * @testdox testFindThrowsOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しない日報を取得しようとすると例外が発生すること.
     */
    public function testFindThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $missing = $this->builder()->create(DailyReportIdentifier::class);

        $repository->find($missing);
    }

    /**
     * @testdox testListSuccessReturnsAllEntities listメソッドで指定した条件の日報を取得できること.
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsAllEntities(Closure $closure): void
    {
        $record = $this->pickRecord();

        $criteria = $closure($this, $record);

        $repository = $this->createRepository();

        $actuals = $repository->list($criteria);

        $expecteds = $this->createListExpected($criteria);

        $this->assertCount($this->createListExpected($criteria)->count(), $actuals);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertRecordProperties($actual);
            });
    }

    /**
     * 検索条件を提供するプロパイダ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class)];

        yield 'user' => [fn (self $self, Record $record): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            [
                'user' => $self->builder()->create(UserIdentifier::class, null, ['value' => $record->user]),
            ]
        )];

        yield 'date' => [fn (self $self, Record $record): Criteria => $self->builder()->create(Criteria::class, null, [
            'date' => $self->builder()->create(DateTimeRange::class, null, [
                'start' => CarbonImmutable::parse($record->date)->setTime(0, 0),
                'end' => CarbonImmutable::parse($record->date)->setTime(23, 59),
            ]),
        ])];

        yield 'isSubmitted' => [fn (self $self, Record $record): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            [
                'isSubmitted' => $record->is_submitted,
            ]
        )];
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
    public function testDeleteSuccessRemoveEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(new DailyReportIdentifier($record->identifier));

        $this->assertDatabaseMissing('daily_reports', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteThrowsOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しない日報を削除しようとすると例外が発生すること.
     */
    public function testDeleteThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $missing = $this->builder()->create(DailyReportIdentifier::class);

        $this->expectException(\OutOfBoundsException::class);

        $repository->delete($missing);
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

    /**
     * listメソッドの期待値を生成する.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->records
            ->when(
                !\is_null($criteria->user()),
                fn (Enumerable $records): Enumerable => $records->where('user', $criteria->user()->value())
            )
            ->when(
                !\is_null($criteria->date()),
                fn (Enumerable $records): Enumerable => $records->whereBetween(
                    'date',
                    [$criteria->date()->start()?->toDateString(), $criteria->date()->end()?->toDateString()]
                )
            )
            ->when(
                !\is_null($criteria->isSubmitted()),
                fn (Enumerable $records): Enumerable => $records->where('is_submitted', $criteria->isSubmitted())
            );
    }
}
