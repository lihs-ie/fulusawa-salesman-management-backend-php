<?php

namespace Tests\Unit\UseCases;

use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\Criteria\Sort;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\VisitRepository;
use App\Exceptions\ConflictException;
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
 *
 * @internal
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規のスケジュールを永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
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
     * @testdox testAddFailureConflictExceptionWithDuplicateIdentifier addメソッドで既に存在するスケジュールを永続化しようとすると例外が発生すること.
     */
    public function testAddFailureConflictExceptionWithDuplicateIdentifier(): void
    {
        $target = $this->instances->random();

        $next = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($next);

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            visitedAt: $parameters['visitedAt'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            hasGraveyard: $parameters['hasGraveyard'],
            note: $parameters['note'],
            result: $parameters['result'],
        );
    }

    /**
     * @testdox testUpdatePersistEntity updateメソッドで既存のスケジュールを上書きして永続化できること.
     */
    public function testUpdatePersistEntity(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(
            Entity::class,
            null,
            ['identifier' => $target->identifier()]
        );

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->update(
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
     * @testdox testUpdateFailureOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しないスケジュールを更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $instance = $this->builder()->create(Entity::class);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($instance);

        $this->expectException(\OutOfBoundsException::class);

        $useCase->update(
            identifier: $parameters['identifier'],
            user: $parameters['user'],
            visitedAt: $parameters['visitedAt'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            hasGraveyard: $parameters['hasGraveyard'],
            note: $parameters['note'],
            result: $parameters['result'],
        );
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
     * @testdox testFindFailureOutOfBoundsExceptionWithMissingIdentifier findメソッドで存在しないスケジュール情報を取得しようとすると例外が発生すること.
     */
    public function testFindFailureOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(VisitIdentifier::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->find($identifier->value());
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでスケジュール情報一覧を取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntitiesWithEmptyCriteria(\Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list($this->deflateCriteria($criteria));

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Entity $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertEntity($expected, $actual);
            })
        ;
    }

    /**
     * 検索条件を提供するへルパ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'empty' => [
            fn (self $self): Criteria => $self->builder()->create(Criteria::class),
        ];

        yield 'user' => [fn (self $self): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            ['user' => $self->instances->random()->user()]
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
                'user' => $self->instances->random()->user(),
                'sort' => $self->builder()->create(Sort::class),
            ]
        )];
    }

    /**
     * @testdox testDeleteSuccessRemoveEntity deleteメソッドで指定したスケジュール情報を削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                VisitRepository::class,
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
     * @testdox testDeleteFailureOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しないスケジュール情報を削除しようとすると例外が発生すること.
     */
    public function testDeleteFailureOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $identifier = $this->builder()->create(VisitIdentifier::class);

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
                VisitRepository::class,
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
                VisitRepository::class,
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
            'result' => $entity->result()->name,
        ];
    }

    /**
     * listメソッドの期待値を生成するへルパ.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->instances
            ->when(!\is_null($criteria->user()), fn (Enumerable $instances): Enumerable => $instances->filter(
                fn (Entity $instance): bool => $criteria->user()->equals($instance->user())
            ))
            ->when(!\is_null($criteria->sort()), fn (Enumerable $instances): Enumerable => match ($criteria->sort()) {
                Sort::VISITED_AT_ASC => $instances->sortBy(fn (Entity $instance): \DateTimeInterface => $instance->visitedAt()),
                Sort::VISITED_AT_DESC => $instances->sortByDesc(fn (Entity $instance): \DateTimeInterface => $instance->visitedAt()),
            })
            ->values()
        ;
    }

    /**
     * 検索条件を配列に変換するへルパ.
     */
    private function deflateCriteria(Criteria $criteria): array
    {
        return [
            'user' => $criteria->user()?->value(),
            'sort' => $criteria->sort()?->name,
        ];
    }
}
