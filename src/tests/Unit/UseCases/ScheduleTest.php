<?php

namespace Tests\Unit\UseCases;

use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\Schedule\Entities\Schedule as Entity;
use App\Domains\Schedule\ScheduleRepository;
use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\FrequencyType;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use App\UseCases\Factories\CommonDomainFactory;
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規のスケジュールを永続化できること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            customer: $parameters['customer'],
            title: $parameters['title'],
            description: $parameters['description'],
            date: $parameters['date'],
            status: $parameters['status'],
            repeatFrequency: $parameters['repeatFrequency'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testPersistSuccessInCaseOnUpdate persistメソッドで既存のスケジュールを上書きして永続化できること.
     */
    public function testPersistSuccessInCaseOnUpdate(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            customer: $parameters['customer'],
            title: $parameters['title'],
            description: $parameters['description'],
            date: $parameters['date'],
            status: $parameters['status'],
            repeatFrequency: $parameters['repeatFrequency'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドでスケジュール情報を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでスケジュール情報一覧を取得できること.
     */
    public function testListSuccessReturnsEntitiesWithEmptyCriteria(): void
    {
        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list([]);

        $expecteds
          ->zip($actuals)
          ->eachSpread(function (Entity $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithCriteria listメソッドでスケジュール情報一覧を取得できること.
     */
    public function testListSuccessReturnsEntitiesWithCriteria(): void
    {
        $criteria = $this->builder()->create(Criteria::class, null, ['filled' => true]);

        $status = $criteria->status();
        $date = $criteria->date();
        $title = $criteria->title();

        $expecteds = $this->instances
          ->when(!\is_null($status), fn (Enumerable $instances) => $instances->filter(fn (Entity $schedule): bool => $schedule->status() === $status))
          ->when(!\is_null($date), function (Enumerable $instances) use ($date): Enumerable {
              return $instances->filter(function (Entity $schedule) use ($date): bool {
                  $candidate = $schedule->date();

                  return $candidate->includes($date->start()) && $candidate->includes($date->end());
              });
          })
          ->when(!\is_null($title), function (Enumerable $instances) use ($title): Enumerable {
              return $instances->filter(fn (Entity $schedule): bool => str_contains($schedule->title(), $title));
          });

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list($this->deflateCriteria($criteria));

        $this->assertSame($expecteds->count(), $actuals->count());

        $expecteds
          ->zip($actuals)
          ->eachSpread(function (Entity $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertEntity($expected, $actual);
          });
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定したスケジュール情報を削除できること.
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
            factory: new CommonDomainFactory(),
        );

        $useCase->delete($target->identifier()->value());

        $removed->each(function (Entity $instance) use ($target): void {
            $this->assertFalse($instance->identifier()->equals($target->identifier()));
        });
    }

    /**
     * @testdox testOfUserSuccessReturnsEntities 指定したユーザーのスケジュール情報一覧を取得できること.
     */
    public function testOfUserSuccessReturnsEntities(): void
    {
        $user = $this->instances->random()->user();

        $expecteds = $this->instances
          ->filter(fn (Entity $schedule): bool => $user->equals($schedule->user()));

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->ofUser($user->value());

        $this->assertSame($expecteds->count(), $actuals->count());

        $expecteds
          ->zip($actuals)
          ->eachSpread(function (Entity $expected, $actual): void {
              $this->assertNotNull($expected);
              $this->assertInstanceOf(Entity::class, $actual);
              $this->assertEntity($expected, $actual);
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
                ScheduleRepository::class,
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
                ScheduleRepository::class,
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
        $this->assertNullOr(
            $expected->customer(),
            $actual->customer(),
            fn (CustomerIdentifier $expected, CustomerIdentifier $actual) => $expected->equals($actual)
        );
        $this->assertTrue($expected->title() === $actual->title());
        $this->assertTrue($expected->description() === $actual->description());
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
        $status = match ($entity->status()) {
            ScheduleStatus::IN_COMPLETE => '1',
            ScheduleStatus::IN_PROGRESS => '2',
            ScheduleStatus::COMPLETED => '3',
        };

        $frequencyType = match ($entity->repeat?->type()) {
            FrequencyType::DAILY, => '1',
            FrequencyType::WEEKLY => '2',
            FrequencyType::MONTHLY => '3',
            FrequencyType::YEARLY => '4',
            null => null
        };

        return [
          'identifier' => $entity->identifier()->value(),
          'user' => $entity->user()->value(),
          'customer' => $entity->customer()?->value(),
          'title' => $entity->title(),
          'description' => $entity->description(),
          'date' => \is_null($entity->date()) ? null : [
            'start' => $entity->date()->start()?->toAtomString(),
            'end' => $entity->date()->end()?->toAtomString(),
          ],
          'status' => $status,
          'repeatFrequency' => \is_null($entity->repeat) ? null : [
            'type' => $frequencyType,
            'interval' => $entity->repeat->interval(),
          ],
        ];
    }

    /**
     * 検索条件を配列に変換するへルパ.
     */
    private function deflateCriteria(Criteria $criteria): array
    {
        $status = match ($criteria->status()) {
            ScheduleStatus::IN_COMPLETE => '1',
            ScheduleStatus::IN_PROGRESS => '2',
            ScheduleStatus::COMPLETED => '3',
            null => null,
        };

        return [
          'status' => $status,
          'date' => \is_null($criteria->date()) ? null : [
            'start' => $criteria->date()->start()?->toAtomString(),
            'end' => $criteria->date()->end()?->toAtomString(),
          ],
          'title' => $criteria->title(),
        ];
    }
}
