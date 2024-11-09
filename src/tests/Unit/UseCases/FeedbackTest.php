<?php

namespace Tests\Unit\UseCases;

use App\Domains\Feedback\Entities\Feedback as Entity;
use App\Domains\Feedback\FeedbackRepository;
use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackStatus;
use App\Domains\Feedback\ValueObjects\FeedbackType;
use App\UseCases\Factories\CommonDomainFactory;
use App\UseCases\Feedback as UseCase;
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規のフィードバックを永続化できること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Entity::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
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
     * @testdox testPersistSuccessInCaseOnUpdate persistメソッドで既存のフィードバックを上書きして永続化できること.
     */
    public function testPersistSuccessInCaseOnUpdate(): void
    {
        $target = $this->instances->random();

        $expected = $this->builder()->create(Entity::class, null, ['identifier' => $target->identifier()]);

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
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
     * @testdox testListSuccessReturnsEntitiesWithEmptyCriteria listメソッドでフィードバック一覧を取得できること.
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
     * @testdox testListSuccessReturnsEntitiesWithFilledCriteria listメソッドでフィードバック一覧を取得できること.
     */
    public function testListSuccessReturnsEntitiesWithFilledCriteria(): void
    {
        $criteria = $this->builder()->create(Criteria::class, null, ['fill' => true, 'sort' => Sort::CREATED_AT_ASC]);

        $sort = fn (Enumerable $instances) => match ($criteria->sort()) {
            Sort::CREATED_AT_ASC => $instances->sortBy(fn (Entity $instance): \DateTimeInterface => ($instance->createdAt())),
            Sort::CREATED_AT_DESC => $instances->sortByDesc(fn (Entity $instance): \DateTimeInterface => ($instance->createdAt())),
            Sort::UPDATED_AT_ASC => $instances->sortBy(fn (Entity $instance): \DateTimeInterface => ($instance->updatedAt())),
            Sort::UPDATED_AT_DESC => $instances->sortByDesc(fn (Entity $instance): \DateTimeInterface => ($instance->updatedAt())),
        };

        $expecteds = $this->instances
            ->filter(fn (Entity $instance): bool => $instance->status() === $criteria->status())
            ->filter(fn (Entity $instance): bool => $instance->type() === $criteria->type())
            ->pipe($sort)
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
     * エンティティからpersistメソッドに使用するパラメータを生成するへルパ.
     */
    private function createParametersFromEntity(Entity $entity): array
    {
        $type = match ($entity->type()) {
            FeedbackType::IMPROVEMENT => '1',
            FeedbackType::PROBLEM => '2',
            FeedbackType::QUESTION => '3',
            FeedbackType::OTHER => '4',
        };

        $status = match ($entity->status()) {
            FeedbackStatus::WAITING => '1',
            FeedbackStatus::IN_PROGRESS => '2',
            FeedbackStatus::COMPLETED => '3',
            FeedbackStatus::NOT_NECESSARY => '4',
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
            FeedbackStatus::WAITING => '1',
            FeedbackStatus::IN_PROGRESS => '2',
            FeedbackStatus::COMPLETED => '3',
            FeedbackStatus::NOT_NECESSARY => '4',
            null => null,
        };

        $type = match ($criteria->type()) {
            FeedbackType::IMPROVEMENT => '1',
            FeedbackType::PROBLEM => '2',
            FeedbackType::QUESTION => '3',
            FeedbackType::OTHER => '4',
            null => null,
        };

        $sort = match ($criteria->sort()) {
            Sort::CREATED_AT_DESC => '1',
            Sort::CREATED_AT_ASC => '2',
            Sort::UPDATED_AT_DESC => '3',
            Sort::UPDATED_AT_ASC => '4',
            null => null,
        };

        return [
            'status' => $status,
            'type' => $type,
            'sort' => $sort,
        ];
    }
}
