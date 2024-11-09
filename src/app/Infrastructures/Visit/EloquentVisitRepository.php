<?php

namespace App\Infrastructures\Visit;

use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\VisitRepository;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitResult;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use App\Infrastructures\Visit\Models\Visit as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴リポジトリ
 */
class EloquentVisitRepository implements VisitRepository
{
    use EloquentCommonDomainRestorer;

    /**
     * コンストラクタ.
     *
     * @param Record $builder
     */
    public function __construct(private readonly Record $builder)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Entity $transactionHistory): void
    {
        $phone = $transactionHistory->phone();
        $address = $transactionHistory->address();

        $this->createQuery()
          ->updateOrCreate(
              ['identifier' => $transactionHistory->identifier()->value()],
              [
              'identifier' => $transactionHistory->identifier()->value(),
              'user' => $transactionHistory->user()->value(),
              'visited_at' => $transactionHistory->visitedAt()->toAtomString(),
              'phone_area_code' => $phone?->areaCode(),
              'phone_local_code' => $phone?->localCode(),
              'phone_subscriber_number' => $phone?->subscriberNumber(),
              'postal_code_first' => $address->postalCode()->first(),
              'postal_code_second' => $address->postalCode()->second(),
              'prefecture' => $address->prefecture->value,
              'city' => $address->city(),
              'street' => $address->street(),
              'building' => $address->building(),
              'has_graveyard' => $transactionHistory->hasGraveyard(),
              'note' => $transactionHistory->note(),
              'result' => $transactionHistory->result()->name,
        ]
          );
    }

    /**
     * {@inheritDoc}
     */
    public function find(VisitIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('Visit not found: %s', $identifier->value()));
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
          ->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(VisitIdentifier $identifier): void
    {
        $target = $this->createQuery()
          ->where('identifier', $identifier->value())
          ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Visit not found: %s', $identifier->value()));
        }

        $target->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function ofUser(UserIdentifier $identifier): Enumerable
    {
        $records = $this->createQuery()
          ->where('user', $identifier->value())
          ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
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
     * レコードからエンティティを復元する.
     *
     * @param Record $record
     * @return Entity
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new VisitIdentifier($record->identifier),
            user: new UserIdentifier($record->user),
            visitedAt: CarbonImmutable::parse($record->visited_at),
            address: $this->restoreAddress($record),
            phone: \is_null($record->phone_area_code) ? null : $this->restorePhone($record),
            hasGraveyard: $record->has_graveyard,
            note: $record->note,
            result: $this->restoreResult($record->result),
        );
    }

    /**
     * 訪問結果を復元する.
     *
     * @param string $status
     * @return VisitResult
     */
    private function restoreResult(string $result): VisitResult
    {
        return match ($result) {
            VisitResult::NO_CONTRACT->name => VisitResult::NO_CONTRACT,
            VisitResult::CONTRACT->name => VisitResult::CONTRACT,
        };
    }
}
