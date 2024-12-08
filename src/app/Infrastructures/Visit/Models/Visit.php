<?php

namespace App\Infrastructures\Visit\Models;

use App\Domains\Visit\ValueObjects\Criteria;
use App\Domains\Visit\ValueObjects\Criteria\Sort;
use App\Domains\Visit\ValueObjects\VisitIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $primaryKey = 'identifier';

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'user',
        'visited_at',
        'phone_number',
        'address',
        'note',
        'has_graveyard',
        'result',
    ];

    /**
     * 訪問識別子と一致するレコードを取得する.
     */
    public function scopeOfIdentifier(Builder $query, VisitIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件と一致するレコードを取得する.
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->user())) {
            $query->where('user', $criteria->user()->value());
        }

        if (!\is_null($criteria->sort())) {
            match ($criteria->sort()) {
                Sort::VISITED_AT_ASC => $query->orderBy('visited_at', 'asc'),
                Sort::VISITED_AT_DESC => $query->orderBy('visited_at', 'desc'),
            };
        }
    }
}
