<?php

namespace App\Infrastructures\Cemetery;

use App\Domains\Cemetery\CemeteryRepository;
use App\Domains\Cemetery\Entities\Cemetery as Entity;
use App\Domains\Cemetery\ValueObjects\CemeteryIdentifier;
use App\Domains\Cemetery\ValueObjects\CemeteryType;
use App\Domains\Cemetery\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Infrastructures\Cemetery\Models\Cemetery as Record;
use App\Infrastructures\Common\AbstractEloquentRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;
use PDOException;

/**
 * 墓地情報リポジトリ
 */
class EloquentCemeteryRepository extends AbstractEloquentRepository implements CemeteryRepository
{
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
    public function persist(Entity $cemetery): void
    {
        try {
            $this->createQuery()
                ->updateOrCreate([
                    'identifier' => $cemetery->identifier()->value(),
                ], [
                    'identifier' => $cemetery->identifier()->value(),
                    'customer' => $cemetery->customer()->value(),
                    'name' => $cemetery->name(),
                    'type' => $cemetery->type()->name,
                    'construction' => $cemetery->construction()->toAtomString(),
                    'in_house' => $cemetery->inHouse(),
                ]);
        } catch (PDOException $exception) {
            $this->handlePDOException(
                exception: $exception,
                messages: $cemetery->identifier()->value()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(CemeteryIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->where('identifier', $identifier->value())
            ->first();

        if (\is_null($record)) {
            throw new \OutOfBoundsException('Cemetery not found');
        }

        return $this->restoreEntity($record);
    }

    /**
     * {@inheritDoc}
     */
    public function ofCustomer(CustomerIdentifier $customer): Enumerable
    {
        $records = $this->createQuery()
            ->where('customer', $customer->value())
            ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}_
     */
    public function list(Criteria $criteria): Enumerable
    {
        $records = $this->createQuery()
            ->ofCriteria($criteria)
            ->get();

        return $records->map(fn (Record $record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(CemeteryIdentifier $identifier): void
    {
        $target = Record::where('identifier', $identifier->value())->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException('Cemetery not found');
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
            identifier: new CemeteryIdentifier($record->identifier),
            customer: new CustomerIdentifier($record->customer),
            name: $record->name,
            type: $this->restoreType($record->type),
            construction: CarbonImmutable::parse($record->construction),
            inHouse: $record->in_house,
        );
    }

    /**
     * レコードから墓地タイプを復元する.
     *
     * @param string $type
     * @return CemeteryType
     */
    private function restoreType(string $type): CemeteryType
    {
        return match ($type) {
            CemeteryType::INDIVIDUAL->name => CemeteryType::INDIVIDUAL,
            CemeteryType::FAMILY->name => CemeteryType::FAMILY,
            CemeteryType::COMMUNITY->name => CemeteryType::COMMUNITY,
            CemeteryType::OTHER->name => CemeteryType::OTHER,
            default => throw new \InvalidArgumentException('Invalid type: ' . $type),
        };
    }
}
