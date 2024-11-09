<?php

namespace App\Infrastructures\Customer;

use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Customer\CustomerRepository;
use App\Domains\Customer\Entities\Customer as Entity;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use App\Infrastructures\Customer\Models\Customer as Record;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 顧客リポジトリ
 */
class EloquentCustomerRepository implements CustomerRepository
{
    use EloquentCommonDomainRestorer;

    /**
     * コンストラクタ.
     *
     * @param Record $builder
     */
    public function __construct(
        private readonly Record $builder,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Entity $customer): void
    {
        $phone = $customer->phone();
        $address = $customer->address();

        $cemeteries = $customer->cemeteries()
          ->map(fn (CemeteryIdentifier $cemetery): string => $cemetery->value())
          ->all();

        $transactionHistories = $customer->transactionHistories()
          ->map(fn (TransactionHistoryIdentifier $transactionHistory): string => $transactionHistory->value())
          ->all();

        $this->createQuery()
          ->updateOrCreate([
            'identifier' => $customer->identifier()->value(),
          ], [
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
            'cemeteries' => json_encode($cemeteries),
            'transaction_histories' => json_encode($transactionHistories),
          ]);
    }

    /**
     * {@inheritDoc}
     */
    public function find(CustomerIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('Customer not found: %s', $identifier->value()));
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function list(): Enumerable
    {
        return $this->createQuery()
          ->get()
          ->map(fn ($record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(CustomerIdentifier $identifier): void
    {
        $target = Record::where('identifier', $identifier->value())->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Customer not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * クエリビルダーを生成する.
     *
     * @return Builder
     */
    private function createQuery(): Builder
    {
        return $this->builder->newQuery();
    }

    /**
     * レコードからユーザーエンティティを復元する.
     *
     * @param Record $record
     * @return Entity
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
     *
     * @param Record $record
     * @return Enumerable
     */
    private function restoreCemeteries(Record $record): Enumerable
    {
        return Collection::wrap(json_decode($record->cemeteries, true))
          ->map(fn (string $cemetery): CemeteryIdentifier => new CemeteryIdentifier($cemetery));
    }

    /**
     * レコードから取引履歴識別子のリストを復元する.
     *
     * @param Record $record
     * @return Enumerable
     */
    private function restoreTransactionHistories(Record $record): Enumerable
    {
        return Collection::wrap(json_decode($record->transaction_histories, true))
          ->map(fn (string $transactionHistory): TransactionHistoryIdentifier => new TransactionHistoryIdentifier($transactionHistory));
    }
}
