<?php

namespace Tests\Unit\Infrastructures\Feedback;

use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\Exceptions\ConflictException;
use App\Infrastructures\Feedback\EloquentFeedbackRepository;
use App\Infrastructures\Feedback\Models\Feedback as Record;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group feedback
 *
 * @coversNothing
 */
class EloquentFeedbackRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * @testdox testAddSuccessPersistEntity addメソッドで新規の日報を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddThrowsConflictExceptionWithDuplicateIdentifier addメソッドで重複する識別子の日報を永続化しようとすると例外が発生すること.
     */
    public function testAddThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(FeedbackIdentifier::class, null, [
                'value' => $record->identifier,
            ]),
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
            'identifier' => $this->builder()->create(FeedbackIdentifier::class, null, [
                'value' => $record->identifier,
            ])
        ]);

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しない日報を更新しようとすると例外が発生すること.
     */
    public function testUpdateThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $entity = $this->builder()->create(Entity::class);

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

        $actual = $repository->find(new FeedbackIdentifier($record->identifier));

        $this->assertRecordProperties($actual);
    }

    /**
     * @testdox testFindThrowsOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しない日報を取得しようとすると例外が発生すること.
     */
    public function testFindThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(FeedbackIdentifier::class);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->find($identifier);
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで指定した条件の日報を取得できること.
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntities(\Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        $repository = $this->createRepository();

        $actuals = $repository->list($criteria);

        $this->assertSame($expecteds->count(), $actuals->count());

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, ?Entity $actual): void {
                $this->assertNotNull($actual);
                $this->assertNotNull($expected);
                $this->assertRecordProperties($actual);
            });
    }

    /**
     * 検索条件を提供するプロパイダ.
     */
    public static function provideCriteria(): \Generator
    {
        // yield 'empty' => [
        //     fn(self $self): Criteria =>
        //     $self->builder()->create(Criteria::class)
        // ];

        yield 'type' => [
            fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
                'type' => Collection::make(FeedbackType::cases())->random()
            ])
        ];

        yield 'status' => [
            fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
                'status' => Collection::make(FeedbackStatus::cases())->random()
            ])
        ];

        yield 'sort' => [
            fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
                'sort' => Collection::make(Sort::cases())->random()
            ])
        ];
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
    private function createRepository(): FeedbackRepository
    {
        return new EloquentFeedbackRepository(new Record());
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('feedbacks', [
            'identifier' => $entity->identifier()->value(),
            'type' => $entity->type()->name,
            'status' => $entity->status()->name,
            'content' => $entity->content(),
            'updated_at' => $entity->updatedAt()->toDateTimeString(),
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
        $this->assertSame($record->type, $actual->type()->name);
        $this->assertSame($record->status, $actual->status()->name);
        $this->assertSame($record->content, $actual->content());
        $this->assertSame(
            $record->updated_at->toDateTimeString(),
            $actual->updatedAt()->toDateTimeString()
        );
    }

    /**
     * listメソッドの期待値を生成する.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->records
            ->when(
                !\is_null($criteria->type()),
                fn (Enumerable $records): Enumerable => $records->where('type', $criteria->type()->name)
            )
            ->when(
                !\is_null($criteria->status()),
                fn (Enumerable $records): Enumerable => $records->where('status', $criteria->status()->name)
            )
            ->pipe(
                function (Enumerable $records) use ($criteria): Enumerable {
                    return match ($criteria->sort()) {
                        Sort::CREATED_AT_ASC => $records->sortBy('created_at'),
                        Sort::CREATED_AT_DESC => $records->sortByDesc('created_at'),
                        Sort::UPDATED_AT_ASC => $records->sortBy('updated_at'),
                        Sort::UPDATED_AT_DESC => $records->sortByDesc('updated_at'),
                        null => $records,
                    };
                }
            )
            ->values();
    }
}
