<?php

namespace App\Infrastructures\Visit;

use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\Entities\Visit as Entity;
use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Domains\Visit\ValueObjects\VisitResult;
use App\Domains\Visit\VisitRepository;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\Support\Common\EloquentCommonDomainDeflator;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use App\Infrastructures\Visit\Models\Visit as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

/**
 * 取引履歴リポジトリ.
 */
class EloquentVisitRepository extends AbstractEloquentRepository implements VisitRepository
{
    use EloquentCommonDomainDeflator;
    use EloquentCommonDomainRestorer;

    /**
     * コンストラクタ.
     */
    public function __construct(private readonly Record $builder) {}

    /**
     * {@inheritDoc}
     */
    public function add(Entity $visit): void
    {
        try {
            $this->createQuery()
                ->create([
                    'identifier' => $visit->identifier()->value(),
                    'user' => $visit->user()->value(),
                    'visited_at' => $visit->visitedAt()->toAtomString(),
                    'phone_number' => \is_null($visit->phone()) ?
                        null : $this->deflatePhoneNumber($visit->phone()),
                    'address' => $this->deflateAddress($visit->address()),
                    'has_graveyard' => $visit->hasGraveyard(),
                    'note' => $visit->note(),
                    'result' => $visit->result()->name,
                ])
            ;
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $visit): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($visit->identifier())
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Visit not found: %s', $visit->identifier()->value()));
        }

        try {
            $target->user = $visit->user()->value();
            $target->visited_at = $visit->visitedAt()->toAtomString();
            $target->phone_number = \is_null($visit->phone()) ?
                null : $this->deflatePhoneNumber($visit->phone());
            $target->address = $this->deflateAddress($visit->address());
            $target->has_graveyard = $visit->hasGraveyard();
            $target->note = $visit->note();
            $target->result = $visit->result()->name;

            $target->save();
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(VisitIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('Visit not found: %s', $identifier->value()));
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
    public function delete(VisitIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('Visit not found: %s', $identifier->value()));
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
     * レコードからエンティティを復元する.
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new VisitIdentifier($record->identifier),
            user: new UserIdentifier($record->user),
            visitedAt: CarbonImmutable::parse($record->visited_at),
            address: $this->restoreAddress($record),
            phone: \is_null($record->phone_number) ? null : $this->restorePhone($record),
            hasGraveyard: $record->has_graveyard,
            note: $record->note,
            result: $this->restoreResult($record->result),
        );
    }

    /**
     * 訪問結果を復元する.
     */
    private function restoreResult(string $result): VisitResult
    {
        return match ($result) {
            VisitResult::NO_CONTRACT->name => VisitResult::NO_CONTRACT,
            VisitResult::CONTRACT->name => VisitResult::CONTRACT,
        };
    }
}
