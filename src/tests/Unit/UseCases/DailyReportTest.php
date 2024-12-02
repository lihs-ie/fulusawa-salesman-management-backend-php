<?php

namespace Tests\Unit\UseCases;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Exceptions\ConflictException;
use App\UseCases\DailyReport as UseCase;
use App\UseCases\Factories\CommonDomainFactory;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group dailyreport
 *
 * @coversNothing
 */
class DailyReportTest extends TestCase
{
    use DependencyBuildable;
    use PersistUseCaseTest;

    /**
     * テストに使用するインスタンス.
     */
    private Enumerable $instances;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->instances = clone $this->createInstances();
    }

    /**
     * @testdox testAddSuccessPersistEntity addメソッドで日報を永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            date: $parameters['date'],
            schedules: $parameters['schedules'],
            visits: $parameters['visits'],
            isSubmitted: $parameters['isSubmitted'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testAddFailureWithDuplicateIdentifier addメソッドで重複した識別子の日報を永続化しようとすると例外が発生すること.
     */
    public function testAddFailureWithDuplicateIdentifier(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(ConflictException::class);

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            date: $parameters['date'],
            schedules: $parameters['schedules'],
            visits: $parameters['visits'],
            isSubmitted: $parameters['isSubmitted'],
        );
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで日報を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->update(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            date: $parameters['date'],
            schedules: $parameters['schedules'],
            visits: $parameters['visits'],
            isSubmitted: $parameters['isSubmitted'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testUpdateFailureWithMissingIdentifier updateメソッドで存在しない日報を更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureWithMissingIdentifier(): void
    {
        $instance = $this->builder()->create(Entity::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $parameters = $this->createParametersFromEntity($instance);

        $useCase->update(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            date: $parameters['date'],
            schedules: $parameters['schedules'],
            visits: $parameters['visits'],
            isSubmitted: $parameters['isSubmitted'],
        );
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで日報を取得すること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testFindFailureWithMissingIdentifier findメソッドで存在しない日報を取得しようとすると例外が発生すること.
     */
    public function testFindFailureWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(DailyReportIdentifier::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->find($identifier->value());
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドで日報一覧を取得すること.
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntitiesWithEmptyCriteria(Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list(
            conditions: $this->deflateCriteria($criteria)
        );

        $this->assertCount($expecteds->count(), $actuals);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class)];

        yield 'with date' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
            'date' => $self->builder()->create(DateTimeRange::class),
        ])];

        yield 'with user' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
            'user' => $self->instances->random()->user(),
        ])];

        yield 'with isSubmitted' => [fn (self $self): Criteria => $self->builder()->create(Criteria::class, null, [
            'isSubmitted' => (bool) \mt_rand(0, 1),
        ])];
    }

    /**
     * @testdox testDeleteSuccessRemoveEntity deleteメソッドで指定した日報を削除すること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                DailyReportRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $instance) use ($target): void {
            $this->assertNotEquals($target->identifier()->value(), $instance->identifier()->value());
        });
    }

    /**
     * @testdox testDeleteFailureWithMissingIdentifier deleteメソッドで存在しない日報を削除しようとすると例外が発生すること.
     */
    public function testDeleteFailureWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(DailyReportIdentifier::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->delete($identifier->value());
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                DailyReportRepository::class,
                null,
                ['onPersist' => $onPersisted]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function createPersistUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                DailyReportRepository::class,
                null,
                ['instances' => $this->instances, 'onPersist' => $onPersisted]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function assertEntity($expected, $actual): void
    {
        $this->assertInstanceOf(Entity::class, $expected);
        $this->assertInstanceOf(Entity::class, $actual);
        $this->assertTrue($expected->identifier()->equals($actual->identifier()));
        $this->assertTrue($expected->user()->equals($actual->user()));
        $this->assertTrue($expected->date()->format('Y-m-d') === $actual->date()->format('Y-m-d'));
        $this->assertTrue($expected->schedules()->diff($actual->schedules())->isEmpty());
        $this->assertTrue($expected->visits()->diff($actual->visits())->isEmpty());
        $this->assertTrue($expected->isSubmitted() === $actual->isSubmitted());
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * エンティティからpersistメソッドに使用するパラメータを生成するへルパ.
     */
    private function createParametersFromEntity(Entity $entity): array
    {
        return [
            'identifier' => $entity->identifier()->value(),
            'user' => $entity->user()->value(),
            'date' => $entity->date()->format('Y-m-d'),
            'schedules' => $entity
                ->schedules()
                ->map(fn (ScheduleIdentifier $schedule): string => $schedule->value())
                ->all(),
            'visits' => $entity
                ->visits()
                ->map(fn (VisitIdentifier $visit): string => $visit->value())
                ->all(),
            'isSubmitted' => $entity->isSubmitted(),
        ];
    }

    /**
     * 検索条件を配列に変換するへルパ.
     */
    private function deflateCriteria(Criteria $criteria): array
    {
        $date = $criteria->date();
        $user = $criteria->user();
        $isSubmitted = $criteria->isSubmitted();

        return [
            'date' => \is_null($date) ? null : [
                'start' => $date->start()?->toAtomString(),
                'end' => $date->end()?->toAtomString(),
            ],
            'user' => $user ? $user->value() : null,
            'isSubmitted' => $isSubmitted,
        ];
    }

    /**
     * listメソッドの期待値を生成するへルパ.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->instances
            ->when(!\is_null($criteria->date()), fn (Enumerable $instances) => $instances->filter(
                fn (Entity $instance): bool => $criteria->date()->includes($instance->date())
            ))
            ->when(!\is_null($criteria->user()), fn (Enumerable $instances) => $instances->filter(
                fn (Entity $instance): bool => $instance->user()->equals($criteria->user())
            ))
            ->when(!\is_null($criteria->isSubmitted()), fn (Enumerable $instances) => $instances->filter(
                fn (Entity $instance): bool => $instance->isSubmitted() === $criteria->isSubmitted()
            ));
    }
}
