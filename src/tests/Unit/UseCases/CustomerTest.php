<?php

namespace Tests\Unit\UseCases;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Common\ValueObjects\PostalCode;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Exceptions\ConflictException;
use App\UseCases\Customer as UseCase;
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
     * @testdox testAddSuccessPersistEntity addメソッドに正しい値を与えたとき永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $expected = $this->builder()->create(Customer::class);

        [$useCase, $persisted] = $this->createEmptyPersistedUseCase();

        $parameters = $this->createParametersFromEntity($expected);

        $useCase->add(
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
     * @testdox testAddThrowsWithConflictIdentifier addメソッドで重複した識別子を指定したとき例外が発生すること.
     */
    public function testAddThrowsWithConflictIdentifier(): void
    {
        [$useCase] = $this->createPersistUseCase();

        $target = $this->instances->random();

        $parameters = $this->createParametersFromEntity($target);

        $this->expectException(ConflictException::class);

        $useCase->add(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            cemeteries: $parameters['cemeteries'],
            transactionHistories: $parameters['transactionHistories'],
        );
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで顧客を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $target = $this->instances->random();

        $next = $this->builder()->create(
            Customer::class,
            null,
            ['identifier' => $target->identifier()]
        );

        [$useCase, $persisted] = $this->createPersistUseCase();

        $parameters = $this->createParametersFromEntity($next);

        $useCase->update(
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
     * @testdox testUpdateThrowsWithMissingIdentifier updateメソッドで存在しない識別子を指定したとき例外が発生すること.
     */
    public function testUpdateThrowsWithMissingIdentifier(): void
    {
        [$useCase] = $this->createPersistUseCase();

        $instance = $this->builder()->create(Customer::class);

        $parameters = $this->createParametersFromEntity($instance);

        $this->expectException(\OutOfBoundsException::class);

        $useCase->update(
            identifier: $parameters['identifier'],
            name: $parameters['name'],
            address: $parameters['address'],
            phone: $parameters['phone'],
            cemeteries: $parameters['cemeteries'],
            transactionHistories: $parameters['transactionHistories'],
        );
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
     * @dataProvider provideListConditions
     */
    public function testListSuccessReturnsEntities(\Closure $closure): void
    {
        $conditions = $closure($this);

        $expecteds = $this->createListExpected($conditions);

        [$useCase] = $this->createPersistUseCase();

        $actuals = $useCase->list($conditions);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (Customer $expected, $actual): void {
                $this->assertNotNull($expected);
                $this->assertInstanceOf(Customer::class, $actual);
                $this->assertEntity($expected, $actual);
            });
    }

    /**
     * listメソッドの検索条件を提供するプロバイダ.
     */
    public static function provideListConditions(): \Generator
    {
        $instance = fn (self $self): Customer => $self->instances->random();

        yield 'name' => [fn (self $self): array => ['name' => $instance($self)->lastName()]];

        yield 'postalCode' => [function (self $self) use ($instance): array {
            $target = $instance($self);

            return [
                'postalCode' => [
                    'first' => $target->address()->postalCode()->first(),
                    'second' => $target->address()->postalCode()->second(),
                ],
            ];
        }];

        yield 'phone' => [function (self $self) use ($instance): array {
            $target = $instance($self);

            return [
                'phone' => [
                    'areaCode' => $target->phone()->areaCode(),
                    'localCode' => $target->phone()->localCode(),
                    'subscriberNumber' => $target->phone()->subscriberNumber(),
                ],
            ];
        }];
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
     * @testdox testDeleteThrowsWithMissingIdentifier deleteメソッドで存在しない識別子を指定したとき例外が発生すること.
     */
    public function testDeleteThrowsWithMissingIdentifier(): void
    {
        [$useCase] = $this->createPersistUseCase();

        $this->expectException(\OutOfBoundsException::class);

        $useCase->delete(
            identifier: $this->builder()->create(CustomerIdentifier::class)->value()
        );
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
            'name' => ['last' => $entity->lastName(), 'first' => $entity->firstName()],
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

    /**
     * 検索条件からlistメソッドの期待値を生成する.
     */
    private function createListExpected(array $conditions): Enumerable
    {
        $name = $conditions['name'] ?? null;

        $postalCode = isset($conditions['postalCode']) ? $this->builder()->create(
            PostalCode::class,
            null,
            ['first' => $conditions['postalCode']['first'], 'second' => $conditions['postalCode']['second']]
        ) : null;

        $phone = isset($conditions['phone']) ? $this->builder()->create(
            PhoneNumber::class,
            null,
            [
                'areaCode' => $conditions['phone']['areaCode'],
                'localCode' => $conditions['phone']['localCode'],
                'subscriberNumber' => $conditions['phone']['subscriberNumber'],
            ]
        ) : null;


        return $this->instances
            ->when(
                !\is_null($name),
                fn (Enumerable $instances): Enumerable => $instances->filter(
                    fn (Customer $instance): bool =>
                    str_contains($instance->lastName(), $name) ||  str_contains($instance->firstName(), $name)
                )
            )
            ->when(
                !\is_null($postalCode),
                fn (Enumerable $instances): Enumerable => $instances->filter(
                    fn (Customer $instance): bool => $postalCode->equals($instance->address()->postalCode())
                )
            )
            ->when(
                !\is_null($phone),
                fn (Enumerable $instances): Enumerable => $instances->filter(
                    fn (Customer $instance): bool => $phone->equals($instance->phone())
                )
            );
    }
}
