<?php

namespace Tests\Unit\UseCases;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\UseCases\Customer as UseCase;
use App\UseCases\Factories\CommonDomainFactory;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;

/**
 * @group unit
 * @group usecases
 * @group customer
 *
 * @coversNothing
 */
class CustomerTest extends TestCase
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
     * @testdox testPersistSuccessInCaseOnCreate persistメソッドで新規の顧客を永続化できること.
     */
    public function testPersistSuccessInCaseOnCreate(): void
    {
        $expected = $this->builder()->create(Customer::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->persist(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            cemeteries: $parameters['cemeteries'],
            transactionHistories: $parameters['transactionHistories'],
        );

        $this->assertPersisted($expected, $persisted, Customer::class);
    }

    /**
     * @testdox testPersistSuccessInCaseOnUpdate persistメソッドで既存の顧客を上書き永続化できること.
     */
    public function testPersistSuccessInCaseOnUpdate(): void
    {
        [$useCase, $persisted] = $this->createPersistUseCase();

        $target = $this->instances->random();

        $next = $this->builder()->create(Customer::class, null, ['identifier' => $target->identifier()]);

        $parameters = $this->createParametersFromEntity($next);

        $useCase->persist(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            cemeteries: $parameters['cemeteries'],
            transactionHistories: $parameters['transactionHistories'],
        );

        $this->assertPersisted($next, $persisted, Customer::class);
    }

    /**
     * @testdox testFindSuccess findメソッドで顧客を取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $expected = $this->instances->random();

        [$useCase] = $this->createPersistUseCase();

        $actual = $useCase->find(
            identifier: $expected->identifier()->value()
        );

        $this->assertEntity($expected, $actual);
    }

    /**
     * @testdox testListSuccessReturnsEntities listメソッドで顧客一覧を取得できること.
     */
    public function testListSuccessReturnsEntities(): void
    {
        $expecteds = clone $this->instances;

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list();

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Customer $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Customer::class, $actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * @testdox testDeleteSuccess deleteメソッドで指定した顧客を削除できること.
     */
    public function testDeleteSuccess(): void
    {
        [$removed, $onRemove] = $this->createRemoveHandler();

        $target = $this->instances->random();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                CustomerRepository::class,
                null,
                ['instances' => $this->instances, 'onRemove' => $onRemove]
            ),
        );

        $useCase->delete(
            identifier: $target->identifier()->value()
        );

        $removed->each(function (Customer $instance) use ($target): void {
            $this->assertFalse($instance->identifier()->equals($target->identifier()));
        });
    }

    /**
     * {@inheritDoc}
     */
    protected function createPersistUseCase(): array
    {
        [$persisted, $onPersist] = $this->createPersistHandler();

        $useCase = new UseCase(
            repository: $this->builder()->create(
                CustomerRepository::class,
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
                CustomerRepository::class,
                null,
                ['onPersist' => $onPersist]
            ),
        );

        return [$useCase, $persisted];
    }

    /**
     * {@inheritDoc}
     */
    protected function assertEntity($expected, $actual): void
    {
        $this->assertInstanceOf(Customer::class, $expected);
        $this->assertInstanceOf(Customer::class, $actual);
        $this->assertTrue($expected->identifier()->equals($actual->identifier()));
        $this->assertTrue($expected->lastName() === $actual->lastName());
        $this->assertTrue($expected->firstName() === $actual->firstName());
        $this->assertTrue($expected->address()->equals($actual->address()));
        $this->assertTrue($expected->phone()->equals($actual->phone()));
        $this->assertTrue($expected->cemeteries()->diff($actual->cemeteries())->isEmpty());
        $this->assertTrue($expected->transactionHistories()->diff($actual->transactionHistories())->isEmpty());
    }

    /**
     * テスト用のインスタンスを生成するへルパ.
     */
    private function createInstances(array $overrides = []): Enumerable
    {
        return $this->builder()->createList(Customer::class, \mt_rand(5, 10), $overrides);
    }

    /**
     * インスタンスからpersistメソッドのパラメータを生成する.
     */
    private function createParametersFromEntity(Customer $entity): array
    {
        return [
            'identifier' => $entity->identifier()->value(),
            'name' => ['lastName' => $entity->lastName(), 'firstName' => $entity->firstName()],
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
            'phone' => [
                'areaCode' => $entity->phone()->areaCode(),
                'localCode' => $entity->phone()->localCode(),
                'subscriberNumber' => $entity->phone()->subscriberNumber(),
            ],
            'cemeteries' => $entity->cemeteries()
                ->map(fn (CemeteryIdentifier $cemetery): string => $cemetery->value())
                ->all(),
            'transactionHistories' => $entity
                ->transactionHistories()
                ->map(fn (TransactionHistoryIdentifier $history): string => $history->value())
                ->all(),
        ];
    }
}
