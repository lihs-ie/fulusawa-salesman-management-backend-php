<?php

namespace App\Infrastructures\DailyReport;

use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Infrastructures\Common\AbstractEloquentRepository;
use App\Infrastructures\DailyReport\Models\DailyReport as Record;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 日報リポジトリ.
 */
class EloquentDailyReportRepository extends AbstractEloquentRepository implements DailyReportRepository
{
    use EloquentCommonDomainRestorer;

    /**
     * コンストラクタ.
     */
    public function __construct(
        private readonly Record $builder
    ) {}

    /**
     * {@inheritDoc}
     */
    public function add(Entity $dailyReport): void
    {
        try {
            $this->createQuery()
                ->create([
                    'identifier' => $dailyReport->identifier()->value(),
                    'user' => $dailyReport->user()->value(),
                    'date' => $dailyReport->date()->toDateString(),
                    'schedules' => $dailyReport->schedules()
                        ->map
                        ->value
                        ->toJson(),
                    'visits' => $dailyReport->visits()
                        ->map
                        ->value
                        ->toJson(),
                    'is_submitted' => $dailyReport->isSubmitted(),
                ])
            ;
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Entity $dailyReport): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($dailyReport->identifier())
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('DailyReport not found %s', $dailyReport->identifier()->value()));
        }

        try {
            $target->user = $dailyReport->user()->value();
            $target->date = $dailyReport->date()->toDateString();
            $target->schedules = $dailyReport->schedules()
                ->map
                ->value
                ->toJson()
            ;
            $target->visits = $dailyReport->visits()
                ->map
                ->value
                ->toJson()
            ;
            $target->is_submitted = $dailyReport->isSubmitted();
            $target->updated_at = CarbonImmutable::now();

            $target->save();
        } catch (\PDOException $exception) {
            $this->handlePDOException($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(DailyReportIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($record)) {
            throw new \OutOfBoundsException(\sprintf('DailyReport not found %s', $identifier->value()));
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
            ->map(fn ($record): Entity => $this->restoreEntity($record))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(DailyReportIdentifier $identifier): void
    {
        $target = $this->createQuery()
            ->ofIdentifier($identifier)
            ->first()
        ;

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('DailyReport not found %s', $identifier->value()));
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
     * レコードからユーザーエンティティを復元する.
     */
    private function restoreEntity(Record $record): Entity
    {
        return new Entity(
            identifier: new DailyReportIdentifier($record->identifier),
            user: new UserIdentifier($record->user),
            date: CarbonImmutable::parse($record->date),
            schedules: $this->restoreSchedules($record),
            visits: $this->restoreVisits($record),
            isSubmitted: $record->is_submitted,
        );
    }

    /**
     * レコードからスケジュール識別子のリストを復元する.
     */
    private function restoreSchedules(Record $record): Enumerable
    {
        return Collection::make(json_decode($record->schedules, null))
            ->map(fn (string $schedule): ScheduleIdentifier => new ScheduleIdentifier($schedule))
        ;
    }

    /**
     * レコードから訪問識別子のリストを復元する.
     */
    private function restoreVisits(Record $record): Enumerable
    {
        return Collection::make(json_decode($record->visits, null))
            ->map(fn (string $visit): VisitIdentifier => new VisitIdentifier($visit));
    }
}
