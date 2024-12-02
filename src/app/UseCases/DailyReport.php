<?php

namespace App\UseCases;

use App\Domains\DailyReport\Entities\DailyReport as Entity;
use App\Domains\DailyReport\DailyReportRepository;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use App\UseCases\Factories\CommonDomainFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 日報ユースケース
 */
class DailyReport
{
    use CommonDomainFactory;

    public function __construct(
        private readonly DailyReportRepository $repository,
    ) {
    }

    /**
     * 日報を永続化する
     *
     * @param string $identifier
     * @param string $user
     * @param string $date
     * @param array $schedules
     * @param array $visits
     * @param bool $isSubmitted
     * @return void
     */
    public function persist(
        string $identifier,
        string $user,
        string $date,
        array $schedules,
        array $visits,
        bool $isSubmitted = false,
    ): void {
        $entity = new Entity(
            identifier: new DailyReportIdentifier($identifier),
            user: new UserIdentifier($user),
            date: CarbonImmutable::parse($date),
            schedules: $this->extractSchedules($schedules),
            visits: $this->extractVisits($visits),
            isSubmitted: $isSubmitted,
        );

        $this->repository->persist($entity);
    }

    /**
     * 日報を取得する
     *
     * @param string $identifier
     * @return Entity
     */
    public function find(string $identifier): Entity
    {
        return $this->repository->find(new DailyReportIdentifier($identifier));
    }

    /**
     * 日報一覧を指定した条件で取得する
     *
     * @param array $conditions
     * @return Enumerable
     */
    public function list(array $conditions): Enumerable
    {
        return $this->repository->list(
            criteria: $this->inflateCriteria($conditions)
        );
    }

    /**
     * 指定した日報を削除する
     *
     * @param string $identifier
     * @return void
     */
    public function delete(string $identifier): void
    {
        $this->repository->delete(new DailyReportIdentifier($identifier));
    }

    /**
     * 配列から予定識別子のリストを抽出する
     *
     * @param array $schedules
     * @return Enumerable<ScheduleIdentifier>
     */
    private function extractSchedules(array $schedules): Enumerable
    {
        return Collection::make($schedules)
            ->map(fn (string $schedule): ScheduleIdentifier => new ScheduleIdentifier($schedule));
    }

    /**
     * 配列から訪問識別子のリストを抽出する
     *
     * @param array $visits
     * @return Enumerable<VisitIdentifier>
     */
    private function extractVisits(array $visits): Enumerable
    {
        return Collection::make($visits)
            ->map(fn (string $visit): VisitIdentifier => new VisitIdentifier(
                value: $visit
            ));
    }

    /**
     * 配列の検索条件からCriteriaを生成する
     *
     * @param array $conditions
     * @return Criteria
     */
    private function inflateCriteria(array $conditions): Criteria
    {
        $date = (isset($conditions['date']))
            ? $this->extractDateTimeRange($conditions['date']) : null;

        $user = (isset($conditions['user']))
            ? new UserIdentifier($this->extractString($conditions, 'user')) : null;

        $isSubmitted = (isset($conditions['isSubmitted']))
            ? $this->extractBoolean($conditions, 'isSubmitted') : null;

        return new Criteria(
            date: $date,
            user: $user,
            isSubmitted: $isSubmitted,
        );
    }
}
