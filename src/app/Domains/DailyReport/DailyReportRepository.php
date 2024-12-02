<?php

namespace App\Domains\DailyReport;

use App\Domains\DailyReport\Entities\DailyReport;
use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 日報リポジトリ
 */
interface DailyReportRepository
{
    /**
     * 日報を永続化する
     *
     * @param DailyReport $dailyReport
     * @return void
     *
     * @throws ConflictException 識別子が重複している場合
     */
    public function add(DailyReport $dailyReport): void;

    /**
     * 日報を更新する
     *
     * @param DailyReport $dailyReport
     * @return void
     *
     * @throws \OutOfBoundsException 日報が存在しない場合
     */
    public function update(DailyReport $dailyReport): void;

    /**
     * 日報を取得する
     *
     * @param DailyReportIdentifier $identifier
     * @return DailyReport
     *
     * @throws \OutOfBoundsException 日報が存在しない場合
     */
    public function find(DailyReportIdentifier $identifier): DailyReport;

    /**
     * 日報一覧を取得する
     *
     * @param Criteria $criteria
     * @return Enumerable<DailyReport>
     */
    public function list(Criteria $criteria): Enumerable;

    /**
     * 日報を削除する
     *
     * @param DailyReportIdentifier $identifier
     * @return void
     *
     * @throws \OutOfBoundsException 日報が存在しない場合
     */
    public function delete(DailyReportIdentifier $identifier): void;
}
