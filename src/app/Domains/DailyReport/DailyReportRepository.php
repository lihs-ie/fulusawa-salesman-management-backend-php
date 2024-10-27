<?php

namespace App\Domains\DailyReport\Entities;

use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 日報リポジトリ
 */
interface DailyReportRepository
{
    public function persist(DailyReport $dailyReport): void;

    public function find(DailyReportIdentifier $identifier): DailyReport;

    public function list(Criteria $criteria): Enumerable;

    public function delete(DailyReportIdentifier $identifier): void;
}
