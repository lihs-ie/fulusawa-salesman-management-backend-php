<?php

namespace Tests\Unit\UseCases;

use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\UseCases\Cemetery as UseCase;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group cemetery
 *
 * @coversNothing
 */
class CemeteryTest extends TestCase
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規の墓地情報を永続化すること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $parameters = [
            'identifier' => Uuid::uuid7()->toString(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(['1', '2', '3', '4'])->random(),
            'construction' => CarbonImmutable::now()->format('Y-m-d H:i:s'),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $useCase->persist(
            identifier: $parameters['identifier'],
            customer: $parameters['customer'],
            name: $parameters['name'],
            type: $parameters['type'],
            construction: $parameters['construction'],
            inHouse: $parameters['inHouse'],
        );

        $expected = $this->createEntityFromParameters($parameters);

        $this->assertPersisted($expected, $persisted);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の墓地情報を上書きして永続化すること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        [$useCase, $persisted] = $this->createPersistUseCase();

        $target = $this->instances->random();

        $parameters = [
            'identifier' => $target->identifier()->value(),
            'customer' => Uuid::uuid7()->toString(),
            'name' => Str::random(\mt_rand(1, 255)),
            'type' => Collection::make(['1', '2', '3'])->random(),
            'construction' => CarbonImmutable::now()->format('Y-m-d H:i:s'),
            'inHouse' => (bool) \mt_rand(0, 1)
        ];

        $expected = $this->createEntityFromParameters($parameters);

        $useCase->persist(
            identifier: $parameters['identifier'],
            customer: $parameters['customer'],
            name: $parameters['name'],
            type: $parameters['type'],
            construction: $parameters['construction'],
            inHouse: $parameters['inHouse'],
        );

        $this->assertPersisted($expected, $persisted);
    }

    /**
     * @testdox testFindSuccessReturnsEntity findメソッドで墓地情報を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find($expected->identifier()->value());

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで墓地情報一覧を取得できること.
     */
    public function testListSuccessReturnsEntities(): void
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
     * @testdox testDeleteSuccess deleteメソッドで指定した墓地情報を削除できること.
     */
    public function testDeleteSuccess(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                CemeteryRepository::class,
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
     * テスト対象のインスタンスを作成するへルパ.
     */
    private function createInstances(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Entity::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * {@inheritDoc}
     */
    protected function createPersistUseCase()
    {
        [$persisted, $onPersist] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                CemeteryRepository::class,
                null,
                ['instances' => $this->instances, 'onPersist' => $onPersist]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function createEmptyPersistedUseCase(): array
    {
        [$persisted, $onPersist] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                CemeteryRepository::class,
                null,
                ['onPersist' => $onPersist]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function assertPersisted(
        Entity $expected,
        Enumerable $persisted,
    ): void {
        $this->assertSame(1, $persisted->get(Entity::class)->count());

        $actual = $persisted->get(Entity::class)->first();
        $this->assertInstanceOf(Entity::class, $actual);
        $this->assertEntity($expected, $actual);
    }

    /**
     * エンティティの内容を比較する.
     */
    private function assertEntity($expected, $actual): void
    {
        $this->assertInstanceOf(Entity::class, $expected);
        $this->assertInstanceOf(Entity::class, $actual);
        $this->assertTrue($expected->identifier()->equals($actual->identifier()));
        $this->assertTrue($expected->customer()->equals($actual->customer()));
        $this->assertTrue($expected->name() === $actual->name());
        $this->assertTrue($expected->type() === $actual->type());
        $this->assertTrue($expected->construction()->toAtomString() === $actual->construction()->toAtomString());
        $this->assertTrue($expected->inHouse() === $actual->inHouse());
    }

    /**
     * パラメータからエンティティを生成するへルパ.
     */
    private function createEntityFromParameters(array $parameters): Entity
    {
        $identifier = $this->builder()->create(
            CemeteryIdentifier::class,
            null,
            ['value' => $parameters['identifier']]
        );

        $customer = $this->builder()->create(
            CustomerIdentifier::class,
            null,
            ['value' => $parameters['customer']]
        );

        $construction = CarbonImmutable::parse($parameters['construction']);

        return $this->builder()->create(
            Entity::class,
            null,
            [
                'identifier' => $identifier,
                'customer' => $customer,
                'name' => $parameters['name'],
                'type' => $this->convertCemeteryType($parameters['type']),
                'construction' => $construction,
                'inHouse' => $parameters['inHouse'],
            ]
        );
    }

    /**
     * 文字列から墓地種別を生成するへルパ.
     *
     * @param string $type
     * @return CemeteryType
     */
    private function convertCemeteryType(string $type): CemeteryType
    {
        return match ($type) {
            '1' => CemeteryType::INDIVIDUAL,
            '2' => CemeteryType::FAMILY,
            '3' => CemeteryType::COMMUNITY,
            '4' => CemeteryType::OTHER,
        };
    }
}
