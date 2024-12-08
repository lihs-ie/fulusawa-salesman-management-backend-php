<?php

namespace App\Infrastructures\Customer;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\Customer\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\Customer\Models\Customer as Record;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 顧客リポジトリ.
 */
class EloquentCustomerRepository extends AbstractEloquentRepository implements CustomerRepository
{
    use EloquentCommonDomainRestorer;

    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly Record $builder,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function add(Entity $customer): void
    {
        $phone = $customer->phone();
        $address = $customer->address();

        try {
            $this->createQuery()
                ->create([
                    'identifier' => $customer->identifier()->value(),
                    'first_name' => $customer->firstName(),
                    'last_name' => $customer->lastName(),
                    'phone_area_code' => $phone->areaCode(),
                    'phone_local_code' => $phone->localCode(),
                    'phone_subscriber_number' => $phone->subscriberNumber(),
                    'postal_code_first' => $address->postalCode()->first(),
                    'postal_code_second' => $address->postalCode()->second(),
                    'prefecture' => $address->prefecture->value,
                    'city' => $address->city(),
                    'street' => $address->street(),
                    'building' => $address->building(),
                    'cemeteries' => $this->serializeIdentifiers($customer->cemeteries()),
                    'transaction_histories' => $this->serializeIdentifiers($customer->transactionHistories()),
                ])
            ;
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $customer): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($customer->identifier())
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Customer not found: %s', $customer->identifier()->value()));
        }

        $phone = $customer->phone();
        $address = $customer->address();

        try {
            $target->first_name = $customer->firstName();
            $target->last_name = $customer->lastName();
            $target->phone_area_code = $phone->areaCode();
            $target->phone_local_code = $phone->localCode();
            $target->phone_subscriber_number = $phone->subscriberNumber();
            $target->postal_code_first = $address->postalCode()->first();
            $target->postal_code_second = $address->postalCode()->second();
            $target->prefecture = $address->prefecture->value;
            $target->city = $address->city();
            $target->street = $address->street();
            $target->building = $address->building();
            $target->cemeteries = $this->serializeIdentifiers($customer->cemeteries());
            $target->transaction_histories = $this->serializeIdentifiers($customer->transactionHistories());

            $target->save();
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(CustomerIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('Customer not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(Criteria $criteria): Enumerable
    {
        return $this->createQuery()
            ->ofCriteria($criteria)
            ->get()
            ->map(fn (Record $record): Entity => $this->restoreEntity($record))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(CustomerIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Customer not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * クエリビルダーを生成する.
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードから顧客エンティティを復元する.
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new CustomerIdentifier($record->identifier),
            firstName: $record->first_name,
            lastName: $record->last_name,
            address: $this->restoreAddress($record),
            phone: $this->restorePhone($record),
            cemeteries: $this->restoreCemeteries($record),
            transactionHistories: $this->restoreTransactionHistories($record),
        );
    }

    /**
     * レコードから墓地情報識別子のリストを復元する.
     */
    private function restoreCemeteries(Record $record): Enumerable
    {
        return Collection::wrap(json_decode($record->cemeteries, true))
            ->map(fn (string $cemetery): CemeteryIdentifier => new CemeteryIdentifier($cemetery))
        ;
    }

    /**
     * レコードから取引履歴識別子のリストを復元する.
     */
    private function restoreTransactionHistories(Record $record): Enumerable
    {
        return Collection::wrap(json_decode($record->transaction_histories, true))
            ->map(fn (string $transactionHistory): TransactionHistoryIdentifier => new TransactionHistoryIdentifier($transactionHistory))
        ;
    }

    /**
     * 識別子のリストをシリアライズする.
     */
    private function serializeIdentifiers(Enumerable $identifiers): string
    {
        return \json_encode(
            $identifiers
                ->map(fn (mixed $identifier): string => $identifier->value())
                ->all()
        );
    }
}
