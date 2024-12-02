<?php

namespace App\Infrastructures\DailyReport;

use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\Infrastructures\Support\Common\EloquentCommonDomainRestorer;
use App\Infrastructures\DailyReport\Models\DailyReport as Record;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 日報リポジトリ.
 */
class EloquentDailyReportRepository implements DailyReportRepository
{
    use EloquentCommonDomainRestorer;

    /**
     * コンストラクタ.
     *
     * @param Record $builder
     */
    public function __construct(
        private readonly Record $builder
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function persist(Entity $dailyReport): void
    {
        $this->createQuery()
          ->updateOrCreate(
              ['identifier' => $dailyReport->identifier()->value()],
              [
              'identifier' => $dailyReport->identifier()->value(),
              'user' => $dailyReport->user()->value(),
              'date' => $dailyReport->date()->toDateString(),
              'schedules' => $dailyReport->schedules()
                ->map(fn (ScheduleIdentifier $schedule): string => $schedule->value())
                ->toJson(),
              'visits' => $dailyReport->visits()
                ->map(fn (VisitIdentifier $visit): string => $visit->value())
                ->toJson(),
              'is_submitted' => $dailyReport->isSubmitted(),
        ]
          );
    }

    /**
     * {@inheritDoc}
     */
    public function find(DailyReportIdentifier $identifier): Entity
    {
        $record = $this->createQuery()
          ->where('identifier', $identifier->value())
          ->first();

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
        $date = $criteria->date();
        $user = $criteria->user();
        $isSubmitted = $criteria->isSubmitted();

        return $this->createQuery()
          ->when(
              !\is_null($date),
              fn (Builder $query): Builder => $query->whereBetween(
                  'date',
                  [$date->start()->toDateString(), $date->end()->toDateString()]
              )
          )
          ->when(
              !\is_null($user),
              fn (Builder $query): Builder => $query->where('user', $user->value())
          )
          ->when(
              !\is_null($isSubmitted),
              fn (Builder $query): Builder => $query->where('is_submitted', $isSubmitted)
          )
          ->get()
          ->map(fn ($record): Entity => $this->restoreEntity($record));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(DailyReportIdentifier $identifier): void
    {
        $target = Record::where('identifier', $identifier->value())->first();

        if (\is_null($target)) {
            throw new \OutOfBoundsException(\sprintf('DailyReport not found %s', $identifier->value()));
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
     *
     * @param Record $record
     * @return Enumerable
     */
    private function restoreSchedules(Record $record): Enumerable
    {
        return Collection::make(json_decode($record->schedules, null))
          ->map(fn (string $schedule): ScheduleIdentifier => new ScheduleIdentifier($schedule));
    }

    /**
     * レコードから訪問識別子のリストを復元する.
     *
     * @param Record $record
     * @return Enumerable
     */
    private function restoreVisits(Record $record): Enumerable
    {
        return Collection::make(json_decode($record->visits, null))
          ->map(fn (string $visit): VisitIdentifier => new VisitIdentifier($visit));
    }
}
