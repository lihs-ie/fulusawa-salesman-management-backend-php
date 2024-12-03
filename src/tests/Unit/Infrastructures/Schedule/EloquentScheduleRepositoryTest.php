<?php

namespace Tests\Unit\Infrastructures\Schedule;

use App\Domains\Common\Utils\CollectionUtil;
use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\ConflictException;
use App\Infrastructures\Schedule\EloquentScheduleRepository;
use App\Infrastructures\Schedule\Models\Schedule as Record;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規の日報を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'creator' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->creator]
            ),
            'updater' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->updater]
            ),
        ]);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドで既存の日報を追加しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                class: ScheduleIdentifier::class,
                overrides: ['value' => $record->identifier]
            ),
            'creator' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->creator]
            ),
            'updater' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->updater]
            ),
        ]);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで既存の日報を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                class: ScheduleIdentifier::class,
                overrides: ['value' => $record->identifier]
            ),
            'creator' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->creator]
            ),
            'updater' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->updater]
            ),
        ]);

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しない日報を更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'creator' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->creator]
            ),
            'updater' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->updater]
            ),
        ]);

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

        $actual = $repository->find(
            $this->builder()->create(
                class: ScheduleIdentifier::class,
                overrides: ['value' => $record->identifier]
            )
        );

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しない日報を取得しようとすると例外が発生すること.
     */
    public function testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->find(
            $this->builder()->create(ScheduleIdentifier::class)
        );
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで指定した条件の日報を取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsAllEntitiesWithEmptyCriteria(\Closure $closure): void
    {
        $record = $this->pickRecord();

        $criteria = $closure($this, $record);

        $expecteds = $this->createListExpected($criteria);

        $repository = $this->createRepository();

        $actuals = $repository->list($criteria);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertRecordProperties($actual);
            });
    }

    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class)];

        yield 'status' => [
            fn (self $self): Criteria =>  $self->builder()
                ->create(Criteria::class, null, [
                    'status' => $self->builder()->create(
                        class: ScheduleStatus::class,
                    ),
                ])
        ];

        yield 'date' => [fn (self $self, Record $record): Criteria => $self->builder()->create(Criteria::class, null, [
            'date' => $self->builder()->create(
                class: DateTimeRange::class,
                overrides: [
                    'start' => CarbonImmutable::parse($record->start)->subDay(),
                    'end' => CarbonImmutable::parse($record->end)->addDay(),
                ]
            ),
        ])];

        yield 'title' => [fn (self $self, Record $record): Criteria => $self->builder()->create(Criteria::class, null, [
            'title' => $record->title,
        ])];

        yield 'user' => [fn (self $self, Record $record): Criteria => $self->builder()->create(Criteria::class, null, [
            'user' => $self->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => Collection::make(\json_decode($record->participants))->random()]
            ),
        ])];
    }

    /**
     * listメソッドの期待値を生成する.
     */
    public function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->records
            ->when(
                !\is_null($criteria->status()),
                fn (Enumerable $records): Enumerable => $records->where('status', $criteria->status()->name)
            )
            ->when(
                !\is_null($criteria->date()),
                fn (Enumerable $records): Enumerable => $records
                    ->when(
                        !\is_null($criteria->date()->start()),
                        fn (Enumerable $records): Enumerable => $records->where(
                            'start',
                            '>=',
                            $criteria->date()->start()
                        )
                    )
                    ->when(
                        !\is_null($criteria->date()->end()),
                        fn (Enumerable $records): Enumerable => $records->where(
                            'end',
                            '<=',
                            $criteria->date()->end()
                        )
                    )
            )
            ->when(
                !\is_null($criteria->title()),
                fn (Enumerable $records): Enumerable => $records->filter(
                    fn (Record $record): bool => \str_contains($record->title, $criteria->title())
                )
            )
            ->when(
                !\is_null($criteria->user()),
                fn (Enumerable $records): Enumerable => $records->filter(
                    fn (Record $record): bool => Collection::make(\json_decode($record->participants))
                        ->contains($criteria->user()->value())
                )
            )
            ->values();
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
            'participants' => $entity->participants()->map->value()->toJson(),
            'creator' => $entity->creator()->value(),
            'updater' => $entity->updater()->value(),
            'customer' => $entity->customer()?->value(),
            'title' => $entity->content()->title(),
            'description' => $entity->content()->description(),
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
        $this->assertSame(
            $record->participants,
            $actual->participants()->map->value()->toJson()
        );
        $this->assertSame($record->creator, $actual->creator()->value());
        $this->assertSame($record->updater, $actual->updater()->value());
        $this->assertSame($record->customer, $actual->customer());
        $this->assertSame($record->title, $actual->content()->title());
        $this->assertSame($record->description, $actual->content()->description());
        $this->assertSame(
            $record->start->format(DATE_ATOM),
            $actual->date()->start()->toAtomString()
        );
        $this->assertSame(
            $record->end->format(DATE_ATOM),
            $actual->date()->end()->toAtomString()
        );
        $this->assertSame($record->status, $actual->status()->name);
    }
}
