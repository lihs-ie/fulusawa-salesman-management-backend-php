<?php

namespace Tests\Unit\Infrastructures\Visit;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\Criteria\Sort;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\VisitRepository;
use App\Exceptions\ConflictException;
use App\Infrastructures\Support\Common\EloquentCommonDomainDeflator;
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
 *
 * @internal
 */
class EloquentVisitRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentCommonDomainDeflator;
    use EloquentRepositoryTest;

    /**
     * @testdox testAddSuccessPersistEntity addメソッドで新規の訪問を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'user' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->user]
            ),
        ]);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドで重複した識別子の訪問を永続化しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                class: VisitIdentifier::class,
                overrides: ['value' => $record->identifier]
            ),
            'user' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->user]
            ),
        ]);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで既存の訪問を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                class: VisitIdentifier::class,
                overrides: ['value' => $record->identifier]
            ),
            'user' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->user]
            ),
        ]);

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しない訪問を更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(VisitIdentifier::class),
            'user' => $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $record->user]
            ),
        ]);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->update($entity);
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
     * @testdox testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しない訪問を取得しようとすると例外が発生すること.
     */
    public function testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->find($this->builder()->create(VisitIdentifier::class));
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで訪問のリストを取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntities(\Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        $repository = $this->createRepository();

        $actuals = $repository->list($criteria);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, ?Entity $actual): void {
                $this->assertNotNull($actual);
                $this->assertNotNull($expected);
                $this->assertRecordProperties($actual);
            })
        ;
    }

    /**
     * 検索条件を提供するプロパイダ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class)];

        yield 'user' => [fn (self $self): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            ['user' => $self->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $self->pickRecord()->user]
            )]
        )];

        yield 'sort' => [fn (self $self): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            ['sort' => $self->builder()->create(Sort::class)]
        )];

        yield 'fulfilled' => [fn (self $self): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            [
                'user' => $self->builder()->create(
                    class: UserIdentifier::class,
                    overrides: ['value' => $self->pickRecord()->user]
                ),
                'sort' => $self->builder()->create(Sort::class),
            ]
        )];
    }

    /**
     * @testdox testDeleteSuccessRemoveEntity deleteメソッドで訪問を削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(
            $this->builder()->create(VisitIdentifier::class, null, ['value' => $record->identifier])
        );

        $this->assertDatabaseMissing('visits', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しない訪問を削除しようとすると例外が発生すること.
     */
    public function testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->delete($this->builder()->create(VisitIdentifier::class));
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
            'phone_number' => \is_null($entity->phone()) ?
                null : $this->deflatePhoneNumber($entity->phone()),
            'address' => $this->deflateAddress($entity->address()),
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
        $this->assertSame($record->phone_number, $this->deflatePhoneNumber($actual->phone()));
        $this->assertSame($record->address, $this->deflateAddress($actual->address()));
        $this->assertSame($record->has_graveyard, $actual->hasGraveyard());
        $this->assertSame($record->note, $actual->note());
        $this->assertSame($record->result, $actual->result()->name);
    }

    /**
     * listメソッドの期待値を生成する.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->records
            ->when(
                !\is_null($criteria->user()),
                fn (Enumerable $records) => $records->where('user', $criteria->user()->value())
            )
            ->when(
                !\is_null($criteria->sort()),
                fn (Enumerable $records) => match ($criteria->sort()) {
                    Sort::VISITED_AT_ASC => $records->sortBy('visited_at'),
                    Sort::VISITED_AT_DESC => $records->sortByDesc('visited_at'),
                }
            )
            ->values()
        ;
    }
}
