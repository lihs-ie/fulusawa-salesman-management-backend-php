<?php

namespace Tests\Unit\Infrastructures\Customer;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Common\ValueObjects\PhoneNumber;
use App\Domains\Common\ValueObjects\PostalCode;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\Customer\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Exceptions\ConflictException;
use App\Infrastructures\Customer\EloquentCustomerRepository;
use App\Infrastructures\Customer\Models\Customer as Record;
use App\Infrastructures\Support\Common\EloquentCommonDomainDeflator;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group customer
 *
 * @coversNothing
 *
 * @internal
 */
class EloquentCustomerRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentCommonDomainDeflator;
    use EloquentRepositoryTest;

    /**
     * @testdox testAddSuccessPersistEntity addメソッドに正しいエンティティを与えたとき永続化できること.
     *
     * @dataProvider provideEntityOverrides
     */
    public function testAddSuccessPersistEntity(\Closure $overrides): void
    {
        $entity = $this->builder()->create(Entity::class, null, $overrides($this));

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * エンティティのオーバーライド配列を提供する.
     */
    public static function provideEntityOverrides(): \Generator
    {
        yield 'cemeteries and transactionHistories are empty' => [fn (self $self): array => [
            'cemeteries' => null,
            'transactionHistories' => null,
        ]];

        yield 'has cemetery' => [fn (self $self): array => [
            'cemeteries' => $self->builder()->createList(CemeteryIdentifier::class, \mt_rand(1, 3)),
        ]];

        yield 'has transaction history' => [fn (self $self): array => [
            'transactionHistories' => $self->builder()->createList(TransactionHistoryIdentifier::class, \mt_rand(1, 3)),
        ]];

        yield 'has cemetery and transaction history' => [fn (self $self): array => [
            'cemeteries' => $self->builder()->createList(CemeteryIdentifier::class, \mt_rand(1, 3)),
            'transactionHistories' => $self->builder()->createList(TransactionHistoryIdentifier::class, \mt_rand(1, 3)),
        ]];
    }

    /**
     * @testdox testAddThrowsConflictExceptionOnDuplicateIdentifier addメソッドで重複する識別子を与えたときConflictExceptionがスローされること.
     */
    public function testAddThrowsConflictExceptionOnDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                CustomerIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存の顧客を更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => new CustomerIdentifier($record->identifier),
        ]);

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しない識別子を与えたときOutOfBoundsExceptionがスローされること.
     */
    public function testUpdateThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->update($entity);
    }

    /**
     * @testdox testFindSuccess findメソッドに正しい識別子を与えたときエンティティを取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(
            $this->builder()->create(CustomerIdentifier::class, null, ['value' => $record->identifier])
        );

        $this->assertPropertyOf($actual);
    }

    /**
     * @testdox testListSuccessReturnsEntities 顧客一覧を取得できること.
     *
     * @dataProvider provideCriteria
     */
    public function testListSuccessReturnsEntities(\Closure $closure): void
    {
        $repository = $this->createRepository();

        $record = $this->pickRecord();

        $criteria = $closure($this, $record);

        $actuals = $repository->list($criteria);

        $expecteds = $this->createListExpected($criteria);

        $expecteds
            ->zip($actuals)
            ->eachSpread(function (?Record $expected, ?Entity $actual): void {
                $this->assertNotNull($expected);
                $this->assertNotNull($actual);
                $this->assertPropertyOf($actual);
            })
        ;
    }

    /**
     * 検索条件を提供するプロバイダ.
     */
    public static function provideCriteria(): \Generator
    {
        yield 'name' => [fn (self $self, Record $record): Criteria => $self->builder()->create(
            Criteria::class,
            null,
            [
                'name' => $record->first_name,
            ]
        )];

        yield 'phone' => [
            function (self $self, Record $record): Criteria {
                $phone = json_decode($record->phone_number, true);

                return $self->builder()->create(
                    Criteria::class,
                    null,
                    [
                        'phone' => $self->builder()->create(PhoneNumber::class, null, [
                            'areaCode' => $phone['areaCode'],
                            'localCode' => $phone['localCode'],
                            'subscriberNumber' => $phone['subscriberNumber'],
                        ]),
                    ]
                );
            },
        ];

        yield 'postal code' => [function (self $self, Record $record): Criteria {
            $address = json_decode($record->address, true);

            return $self->builder()->create(
                Criteria::class,
                null,
                [
                    'postalCode' => $self->builder()->create(PostalCode::class, null, [
                        'first' => $address['postalCode']['first'],
                        'second' => $address['postalCode']['second'],
                    ]),
                ]
            );
        }];
    }

    /**
     * @testdox testDeleteSuccessRemoveEntity removeメソッドで顧客を削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete($this->builder()->create(
            class: CustomerIdentifier::class,
            overrides: ['value' => $record->identifier]
        ));

        $this->assertDatabaseMissing('customers', ['identifier' => $record->identifier]);
    }

    /**
     * @testdox testDeleteThrowsOutOfBoundsExceptionWithMissingIdentifier deleteメソッドで存在しない識別子を与えたときOutOfBoundsExceptionがスローされること.
     */
    public function testDeleteThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->delete($this->builder()->create(CustomerIdentifier::class));
    }

    /**
     * {@inheritDoc}
     */
    protected function createRecords(): Enumerable
    {
        return $this->factory(Record::class)
            ->createMany(\mt_rand(5, 10))
        ;
    }

    /**
     * リポジトリを生成するへルパ.
     */
    private function createRepository(): CustomerRepository
    {
        return new EloquentCustomerRepository(new Record());
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $cemeteries = $entity->cemeteries()
            ->map(
                fn (CemeteryIdentifier $cemetery): string => $cemetery->value()
            )
            ->all()
        ;

        $transactionHistories = $entity->transactionHistories()
            ->map(
                fn (TransactionHistoryIdentifier $transactionHistory): string => $transactionHistory->value()
            )
            ->all()
        ;

        $this->assertDatabaseHas('customers', [
            'identifier' => $entity->identifier()->value(),
            'first_name' => $entity->firstName(),
            'last_name' => $entity->lastName(),
            'address' => $this->deflateAddress($entity->address()),
            'phone_number' => $this->deflatePhoneNumber($entity->phone()),
            'cemeteries' => \json_encode($cemeteries),
            'transaction_histories' => \json_encode($transactionHistories),
        ]);
    }

    /**
     * レコードのプロパティを比較する.
     */
    private function assertPropertyOf(Entity $actual): void
    {
        $record = $this->records->first(
            fn (Record $record): bool => $record->identifier === $actual->identifier()->value()
        );

        $this->assertNotNull($record);
        $this->assertSame($record->first_name, $actual->firstName());
        $this->assertSame($record->last_name, $actual->lastName());
        $this->assertSame($record->phone_number, $this->deflatePhoneNumber($actual->phone()));
        $this->assertSame($record->address, $this->deflateAddress($actual->address()));

        $expectedCemeteries = json_decode($record->cemeteries, true);
        $this->assertSame(count($expectedCemeteries), $actual->cemeteries()->count());
        Collection::make($expectedCemeteries)
            ->zip($actual->cemeteries())
            ->eachSpread(function ($expected, $actual): void {
                $this->assertInstanceOf(CemeteryIdentifier::class, $actual);
                $this->assertSame($expected, $actual->value());
            })
        ;

        $expectedHistories = json_decode($record->transaction_histories, true);
        $this->assertSame(count($expectedHistories), $actual->transactionHistories()->count());
        Collection::make($expectedHistories)
            ->zip($actual->transactionHistories())
            ->eachSpread(function ($expected, $actual): void {
                $this->assertInstanceOf(TransactionHistoryIdentifier::class, $actual);
                $this->assertSame($expected, $actual->value());
            })
        ;
    }

    /**
     * listメソッドの期待値を生成する.
     */
    private function createListExpected(Criteria $criteria): Enumerable
    {
        return $this->records
            ->when(
                !\is_null($criteria->name()),
                function (Enumerable $records) use ($criteria): Enumerable {
                    $name = $criteria->name();

                    return $records->filter(fn (Record $record): bool => \str_contains($record->first_name, $name) || \str_contains($record->last_name, $name));
                }
            )
            ->when(
                !\is_null($criteria->phone()),
                fn (Enumerable $records): Enumerable => $records
                    ->where('phone_number', $this->deflatePhoneNumber($criteria->phone()))
            )
            ->when(
                !\is_null($criteria->postalCode()),
                fn (Enumerable $records) => $records
                    ->filter(function (Record $record) use ($criteria): bool {
                        $address = json_decode($record->address, true);
                        $postalCode = $criteria->postalCode();

                        if ($postalCode->first() !== $address['postalCode']['first']) {
                            return false;
                        }

                        return $postalCode->second() === $address['postalCode']['second'];
                    })
            );
    }
}
