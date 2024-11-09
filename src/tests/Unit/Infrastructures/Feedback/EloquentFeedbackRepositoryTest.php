<?php

namespace Tests\Unit\Infrastructures\Feedback;

use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use App\Infrastructures\Feedback\EloquentFeedbackRepository;
use App\Infrastructures\Feedback\Models\Feedback as Record;
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
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の日報を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $entity = $this->builder()->create(Entity::class);

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
            'identifier' => new FeedbackIdentifier($record->identifier),
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

        $actual = $repository->find(new FeedbackIdentifier($record->identifier));

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
                fn (Record $record): bool => $record->type === $criteria->type()->name
                    && $record->status === $criteria->status()->name
            )->pipe(
                function (Enumerable $records) use ($criteria): Enumerable {
                    return match ($criteria->sort()) {
                        Sort::CREATED_AT_ASC => $records->sortBy('created_at'),
                        Sort::CREATED_AT_DESC => $records->sortByDesc('created_at'),
                        Sort::UPDATED_AT_ASC => $records->sortBy('updated_at'),
                        Sort::UPDATED_AT_DESC => $records->sortByDesc('updated_at'),
                    };
                }
            );

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
}
