<?php

namespace Tests\Unit\UseCases;

use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\Entities\Feedback;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\Exceptions\ConflictException;
use App\UseCases\Factories\CommonDomainFactory;
use App\UseCases\Feedback as UseCase;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group feedback
 *
 * @coversNothing
 */
class FeedbackTest extends TestCase
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
     * @testdox testAddSuccessPersistEntity addメソッドで新規のフィードバックを永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
            identifier: $parameters['identifier'],
            type: $parameters['type'],
            status: $parameters['status'],
            content: $parameters['content'],
            createdAt: $parameters['createdAt'],
            updatedAt: $parameters['updatedAt'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testAddFailureWithDuplicateIdentifier addメソッドで重複する識別子のフィードバックを永続化しようとすると例外が発生すること.
     */
    public function testAddFailureWithDuplicateIdentifier(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $parameters['identifier'],
            type: $parameters['type'],
            status: $parameters['status'],
            content: $parameters['content'],
            createdAt: $parameters['createdAt'],
            updatedAt: $parameters['updatedAt'],
        );
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで既存のフィードバックを上書きして永続化できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->update(
            identifier: $parameters['identifier'],
            type: $parameters['type'],
            status: $parameters['status'],
            content: $parameters['content'],
            createdAt: $parameters['createdAt'],
            updatedAt: $parameters['updatedAt'],
        );

        $this->assertPersisted($expected, $persisted, Entity::class);
    }

    /**
     * @testdox testUpdateFailureWithMissingIdentifier updateメソッドで存在しないフィードバックを更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureWithMissingIdentifier(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $this->expectException(\OutOfBoundsException::class);

        $useCase->update(
            identifier: $parameters['identifier'],
            type: $parameters['type'],
            status: $parameters['status'],
            content: $parameters['content'],
            createdAt: $parameters['createdAt'],
            updatedAt: $parameters['updatedAt'],
        );
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドでフィードバックを取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testFindFailureWithMissingIdentifier findメソッドで存在しないフィードバックを取得しようとすると例外が発生すること.
     */
    public function testFindFailureWithMissingIdentifier(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->find($expected->identifier()->value());
    }

    /**
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでフィードバック一覧を取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntities(Closure $closure): void
    {
        $criteria = $closure($this);

        $expecteds = $this->createListExpected($criteria);

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list($this->deflateCriteria($criteria));

        $this->assertCount($expecteds->count(), $actuals);

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
        yield 'empty' => [fn (self $self) => $self->builder()->create(Criteria::class)];

        yield 'status' => [fn (self $self) => $self->builder()->create(Criteria::class, null, [
            'status' => Collection::make(FeedbackStatus::cases())->random(),
        ])];

        yield 'type' => [fn (self $self) => $self->builder()->create(Criteria::class, null, [
            'type' => Collection::make(FeedbackType::cases())->random(),
        ])];

        yield 'sort' => [fn (self $self) => $self->builder()->create(Criteria::class, null, [
            'sort' => Collection::make(Sort::cases())->random(),
        ])];
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersisted] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                FeedbackRepository::class,
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
                FeedbackRepository::class,
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
        $this->assertTrue($expected->status() === $actual->status());
        $this->assertTrue($expected->content() === $actual->content());
        $this->assertTrue($expected->createdAt()->toAtomString() === $actual->createdAt()->toAtomString());
        $this->assertTrue($expected->updatedAt()->toAtomString() === $actual->updatedAt()->toAtomString());
    }

    /**
     * テストに使用するインスタンスを生成するへルパ.
     */
    private function createInstances(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * listメソッドの期待値を生成するへルパ.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->instances
            ->when(!\is_null($criteria->status()), fn (Enumerable $instances): Enumerable => $instances->filter(
                fn (Entity $instance): bool => $instance->status() === $criteria->status()
            ))
            ->when(!\is_null($criteria->type()), fn (Enumerable $instances): Enumerable => $instances->filter(
                fn (Entity $instance): bool => $instance->type() === $criteria->type()
            ))
            ->when(!\is_null($criteria->sort()), fn (Enumerable $instances): Enumerable =>
            $instances->pipe(fn (Enumerable $instances): Enumerable => match ($criteria->sort()) {
                Sort::CREATED_AT_DESC => $instances->sortByDesc(fn (Entity $instance): \DateTimeInterface => ($instance->createdAt())),
                Sort::CREATED_AT_ASC => $instances->sortBy(fn (Entity $instance): \DateTimeInterface => ($instance->createdAt())),
                Sort::UPDATED_AT_DESC => $instances->sortByDesc(fn (Entity $instance): \DateTimeInterface => ($instance->createdAt())),
                Sort::UPDATED_AT_ASC => $instances->sortBy(fn (Entity $instance): \DateTimeInterface => ($instance->updatedAt())),
                null => $instances
            }))
            ->values();
    }

    /**
     * エンティティからaddメソッドに使用するパラメータを生成するへルパ.
     */
    private function createParametersFromEntity(Entity $entity): array
    {
        $type = match ($entity->type()) {
            FeedbackType::IMPROVEMENT => FeedbackType::IMPROVEMENT->name,
            FeedbackType::PROBLEM => FeedbackType::PROBLEM->name,
            FeedbackType::QUESTION => FeedbackType::QUESTION->name,
            FeedbackType::OTHER => FeedbackType::OTHER->name,
        };

        $status = match ($entity->status()) {
            FeedbackStatus::WAITING => FeedbackStatus::WAITING->name,
            FeedbackStatus::IN_PROGRESS => FeedbackStatus::IN_PROGRESS->name,
            FeedbackStatus::COMPLETED => FeedbackStatus::COMPLETED->name,
            FeedbackStatus::NOT_NECESSARY => FeedbackStatus::NOT_NECESSARY->name,
        };

        return [
            'identifier' => $entity->identifier()->value(),
            'type' => $type,
            'status' => $status,
            'content' => $entity->content(),
            'createdAt' => $entity->createdAt()->toAtomString(),
            'updatedAt' => $entity->updatedAt()->toAtomString(),
        ];
    }

    /**
     * 検索条件を配列に変換するへルパ.
     */
    private function deflateCriteria(Criteria $criteria): array
    {
        $status = match ($criteria->status()) {
            FeedbackStatus::WAITING => FeedbackStatus::WAITING->name,
            FeedbackStatus::IN_PROGRESS => FeedbackStatus::IN_PROGRESS->name,
            FeedbackStatus::COMPLETED => FeedbackStatus::COMPLETED->name,
            FeedbackStatus::NOT_NECESSARY => FeedbackStatus::NOT_NECESSARY->name,
            null => null,
        };

        $type = match ($criteria->type()) {
            FeedbackType::IMPROVEMENT => FeedbackType::IMPROVEMENT->name,
            FeedbackType::PROBLEM => FeedbackType::PROBLEM->name,
            FeedbackType::QUESTION => FeedbackType::QUESTION->name,
            FeedbackType::OTHER => FeedbackType::OTHER->name,
            null => null,
        };

        $sort = match ($criteria->sort()) {
            Sort::CREATED_AT_DESC => Sort::CREATED_AT_DESC->name,
            Sort::CREATED_AT_ASC => Sort::CREATED_AT_ASC->name,
            Sort::UPDATED_AT_DESC => Sort::UPDATED_AT_DESC->name,
            Sort::UPDATED_AT_ASC => Sort::UPDATED_AT_ASC->name,
            null => null,
        };

        return [
            'status' => $status,
            'type' => $type,
            'sort' => $sort,
        ];
    }
}
