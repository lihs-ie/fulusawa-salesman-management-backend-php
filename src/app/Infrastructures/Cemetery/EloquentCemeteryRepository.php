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
    public function add(Entity $cemetery): void
    {
        try {
            $this->createQuery()
                ->create([
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
    public function update(Entity $cemetery): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($cemetery->identifier())
            ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Cemetery not found. identifier: %s', $cemetery->identifier()->value()));
        }

        try {
            $target->customer = $cemetery->customer()->value();
            $target->name = $cemetery->name();
            $target->type = $cemetery->type()->name;
            $target->construction = $cemetery->construction()->toAtomString();
            $target->in_house = $cemetery->inHouse();
            $target->updated_at = CarbonImmutable::now();

            $target->save();
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
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Cemetery not found. identifier: %s', $identifier->value()));
        }

        return $this->restoreEntity($target);
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
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Cemetery not found. identifier: %s', $identifier->value()));
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
