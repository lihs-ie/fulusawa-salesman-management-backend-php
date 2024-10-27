<?php

namespace App\Domains\Visit;

use App\Domains\Visit\Entities\Visit;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Illuminate\Support\Enumerable;

/**
 * 訪問リポジトリ
 */
interface VisitRepository
{
    public function persist(Visit $visit): void;

    public function find(VisitIdentifier $identifier): Visit;

    public function list(): Enumerable;

    public function delete(VisitIdentifier $identifier): void;
}
