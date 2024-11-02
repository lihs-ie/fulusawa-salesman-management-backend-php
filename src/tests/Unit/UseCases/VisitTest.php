<?php

namespace Tests\Unit\UseCases;

use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\VisitRepository;
use App\Domains\Visit\ValueObjects\Role;
use App\Domains\Visit\ValueObjects\VisitResult;
use App\UseCases\Factories\CommonDomainFactory;
use App\UseCases\Visit as UseCase;
use Illuminate\Support\Enumerable;
use Tests\Support\Assertions\NullableValueComparable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group visit
 *
 * @coversNothing
 */
class VisitTest extends TestCase
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
            visitedAt: $parameters['visitedAt'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            hasGraveyard: $parameters['hasGraveyard'],
            note: $parameters['note'],
            result: $parameters['result'],
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
            visitedAt: $parameters['visitedAt'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            hasGraveyard: $parameters['hasGraveyard'],
            note: $parameters['note'],
            result: $parameters['result'],
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

        $actuals = $useCase->list();

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
        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list();

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
                VisitRepository::class,
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
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                VisitRepository::class,
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
                VisitRepository::class,
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
        $this->assertTrue($expected->visitedAt()->toAtomString() === $actual->visitedAt()->toAtomString());
        $this->assertTrue($expected->address()->equals($actual->address()));
        $this->assertNullOr(
            $expected->phone(),
            $actual->phone(),
            fn ($expected, $actual) => $expected->equals($actual)
        );
        $this->assertTrue($expected->hasGraveyard() === $actual->hasGraveyard());
        $this->assertTrue($expected->note() === $actual->note());
        $this->assertTrue($expected->result() === $actual->result());
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
        $result = match ($entity->result()) {
            VisitResult::NO_CONTRACT => '0',
            VisitResult::CONTRACT => '1'
        };

        return [
          'identifier' => $entity->identifier()->value(),
          'user' => $entity->user()->value(),
          'visitedAt' => $entity->visitedAt()->toAtomString(),
          'address' => [
            'postalCode' => [
              'first' => $entity->address()->postalCode()->first(),
              'second' => $entity->address()->postalCode()->second(),
            ],
            'prefecture' => $entity->address()->prefecture()->value,
            'city' => $entity->address()->city(),
            'street' => $entity->address()->street(),
            'building' => $entity->address()->building(),
          ],
          'phone' => \is_null($entity->phone()) ? null : [
            'areaCode' => $entity->phone()->areaCode(),
            'localCode' => $entity->phone()->localCode(),
            'subscriberNumber' => $entity->phone()->subscriberNumber(),
          ],
          'hasGraveyard' => $entity->hasGraveyard(),
          'note' => $entity->note(),
          'result' => $result,
        ];
    }
}
