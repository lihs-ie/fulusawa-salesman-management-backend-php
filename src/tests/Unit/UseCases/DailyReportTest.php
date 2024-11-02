<?php

namespace Tests\Unit\UseCases;

use App\Domains\Common\ValueObjects\DateTimeRange;
use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\UseCases\DailyReport as UseCase;
use App\UseCases\Factories\CommonDomainFactory;
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規の日報を永続化すること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
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
     * @testdox testPersistSuccessInCaseOnUpdate persistメソッドで既存の日報を更新すること.
     */
    public function testPersistSuccessInCaseOnUpdate(): void
    {
        $expected = $this->instances->random();

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
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
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドで日報一覧を取得すること.
     */
    public function testListSuccessReturnsEntitiesWithEmptyCriteria(): void
    {
        $criteria = $this->builder()->create(Criteria::class);

        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list(
            conditions: $this->deflateCriteria($criteria)
        );

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithFilledCriteria listメソッドで日報一覧を取得すること.
     */
    public function testListSuccessReturnsEntitiesWithFilledCriteria(): void
    {
        $instance = $this->instances->random();

        $date = $this->builder()->create(DateTimeRange::class, null, [
            'start' => $instance->date()->setTime(0, 0),
            'end' => $instance->date()->setTime(23, 59),
        ]);

        $criteria = $this->builder()->create(
            Criteria::class,
            null,
            ['user' => $instance->user(), 'date' => $date, 'isSubmitted' => $instance->isSubmitted()]
        );

        $expecteds = $this->instances
            ->filter(fn (Entity $instance): bool => $date->includes($instance->date()))
            ->filter(fn (Entity $instance): bool => $instance->user()->equals($instance->user()))
            ->filter(fn (Entity $instance): bool => $instance->isSubmitted() === $criteria->isSubmitted());

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list(conditions: $this->deflateCriteria($criteria));

        $this->assertInstanceOf(Collection::class, $actuals);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定した日報を削除すること.
     */
    public function testDeleteSuccess(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                DailyReportRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
            factory: new CommonDomainFactory(),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $instance) use ($target): void {
            $this->assertNotEquals($target->identifier()->value(), $instance->identifier()->value());
        });
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
            factory: new CommonDomainFactory(),
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
            factory: new CommonDomainFactory(),
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
                'start' => $date ? $date->start()->toAtomString() : null,
                'end' => $date ? $date->end()->toAtomString() : null,
            ],
            'user' => $user ? $user->value() : null,
            'isSubmitted' => $isSubmitted,
        ];
    }
}
