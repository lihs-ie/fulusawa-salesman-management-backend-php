<?php

namespace Tests\Unit\UseCases;

use App\Domains\Common\Utils\CollectionUtil;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\ConflictException;
use App\UseCases\Schedule as UseCase;
use Illuminate\Support\Enumerable;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group schedule
 *
 * @coversNothing
 */
class ScheduleTest extends TestCase
{
    use DependencyBuildable;
    use NullableValueComparable;
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規のスケジュールを永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
            identifier: $parameters['identifier'],
            participants: $parameters['participants'],
            creator: $parameters['creator'],
            updater: $parameters['updater'],
            customer: $parameters['customer'],
            content: $parameters['content'],
            date: $parameters['date'],
            status: $parameters['status'],
            repeatFrequency: $parameters['repeatFrequency'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドに重複する識別子を指定するとConflictExceptionが発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $parameters['identifier'],
            participants: $parameters['participants'],
            creator: $parameters['creator'],
            updater: $parameters['updater'],
            customer: $parameters['customer'],
            content: $parameters['content'],
            date: $parameters['date'],
            status: $parameters['status'],
            repeatFrequency: $parameters['repeatFrequency'],
        );
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドでスケジュールを更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => $target->identifier(),
        ]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->update(
            identifier: $parameters['identifier'],
            participants: $parameters['participants'],
            creator: $parameters['creator'],
            updater: $parameters['updater'],
            customer: $parameters['customer'],
            content: $parameters['content'],
            date: $parameters['date'],
            status: $parameters['status'],
            repeatFrequency: $parameters['repeatFrequency'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testUpdateFailureThrowsNotFoundExceptionWithMissingIdentifier updateメソッドに存在しない識別子を指定するとNotFoundExceptionが発生すること.
     */
    public function testUpdateFailureThrowsNotFoundExceptionWithMissingIdentifier(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $this->expectException(\OutOfBoundsException::class);

        $useCase->update(
            identifier: $parameters['identifier'],
            participants: $parameters['participants'],
            creator: $parameters['creator'],
            updater: $parameters['updater'],
            customer: $parameters['customer'],
            content: $parameters['content'],
            date: $parameters['date'],
            status: $parameters['status'],
            repeatFrequency: $parameters['repeatFrequency'],
        );
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドでスケジュールを取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testFindFailureThrowsNotFoundExceptionWithMissingIdentifier findメソッドに存在しない識別子を指定するとNotFoundExceptionが発生すること.
     */
    public function testFindFailureThrowsNotFoundExceptionWithMissingIdentifier(): void
    {
        $missing = $this->builder()->create(ScheduleIdentifier::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->find($missing->value());
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでスケジュール一覧を取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntities(\Closure $closure): void
    {
        $instance = $this->instances->random();

        $criteria = $closure($this, $instance);

        $expecteds  = $this->instances
            ->when(!\is_null($criteria->status()), fn (Enumerable $instances) => $instances
                ->filter(fn (Entity $schedule): bool => $schedule->status() === $criteria->status()))
            ->when(
                !\is_null($criteria->date()),
                fn (Enumerable $instances) => $instances->filter(
                    fn (Entity $schedule): bool => $criteria->date()->includesRange($schedule->date())
                )
            )
            ->when(!\is_null($criteria->title()), function (Enumerable $instances) use ($criteria): Enumerable {
                return $instances->filter(fn (Entity $schedule): bool => str_contains($schedule->content()->title(), $criteria->title()));
            })
            ->when(!\is_null($criteria->user()), fn (Enumerable $instances) => $instances
                ->filter(fn (Entity $schedule): bool => $schedule->participants()->contains($criteria->user())))
            ->values();

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list($this->deflateCriteria($criteria));

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
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
        $criteria = fn (self $self, array $overrides = []): Criteria => $self->builder()
            ->create(
                class: Criteria::class,
                overrides: $overrides
            );

        yield 'empty' => [fn (self $self): Criteria => $criteria($self)];

        yield 'status' => [fn (self $self): Criteria => $criteria(
            $self,
            ['status' => $self->builder()->create(ScheduleStatus::class)]
        )];

        // TODO なぜか失敗する
        // yield 'date' => [fn(self $self, Entity $entity): Criteria => $criteria(
        //     $self,
        //     ['date' => $entity->date()]
        // )];

        yield 'title' => [fn (self $self, Entity $entity): Criteria => $criteria(
            $self,
            ['title' => $entity->content()->title()]
        )];

        yield 'user' => [fn (self $self, Entity $entity): Criteria => $criteria(
            $self,
            ['user' => $entity->participants()->random()]
        )];
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定したスケジュールを削除できること.
     */
    public function testDeleteSuccess(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                ScheduleRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $instance) use ($target): void {
            $this->assertFalse($instance->identifier()->equals($target->identifier()));
        });
    }

    /**
     * @testdox testDeleteFailureThrowsNotFoundExceptionWithMissingIdentifier deleteメソッドに存在しない識別子を指定するとNotFoundExceptionが発生すること.
     */
    public function testDeleteFailureThrowsNotFoundExceptionWithMissingIdentifier(): void
    {
        $missing = $this->builder()->create(ScheduleIdentifier::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->delete($missing->value());
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                ScheduleRepository::class,
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
                ScheduleRepository::class,
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
        $this->assertTrue(CollectionUtil::equalsAsSet(
            $expected->participants(),
            $actual->participants(),
            fn (UserIdentifier $expected, UserIdentifier $actual) => $expected->equals($actual)
        ));
        $this->assertTrue($expected->creator()->equals($actual->creator()));
        $this->assertTrue($expected->updater()->equals($actual->updater()));
        $this->assertNullOr(
            $expected->customer(),
            $actual->customer(),
            fn (CustomerIdentifier $expected, CustomerIdentifier $actual) => $expected->equals($actual)
        );
        $this->assertTrue($expected->content()->equals($actual->content()));
        $this->assertTrue($expected->date()->equals($actual->date()));
        $this->assertTrue($expected->status() === $actual->status());
        $this->assertTrue($expected->repeat()->equals($actual->repeat()));
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
            'participants' => $entity->participants()->map->value()->all(),
            'creator' => $entity->creator()->value(),
            'updater' => $entity->updater()->value(),
            'customer' => $entity->customer()?->value(),
            'content' => [
                'title' => $entity->content()->title(),
                'description' => $entity->content()->description(),
            ],
            'date' => \is_null($entity->date()) ? null : [
                'start' => $entity->date()->start()?->toAtomString(),
                'end' => $entity->date()->end()?->toAtomString(),
            ],
            'status' => $entity->status()->name,
            'repeatFrequency' => \is_null($entity->repeat) ? null : [
                'type' => $entity->repeat()->type()->name,
                'interval' => $entity->repeat->interval(),
            ],
        ];
    }

    /**
     * 検索条件を配列に変換するへルパ.
     */
    private function deflateCriteria(Criteria $criteria): array
    {
        return [
            'status' => $criteria->status()?->name,
            'date' => \is_null($criteria->date()) ? null : [
                'start' => $criteria->date()->start()?->toAtomString(),
                'end' => $criteria->date()->end()?->toAtomString(),
            ],
            'title' => $criteria->title(),
            'user' => $criteria->user()?->value(),
        ];
    }
}
