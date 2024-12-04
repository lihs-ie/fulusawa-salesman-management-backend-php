<?php

namespace App\Infrastructures\Schedule\Models;

use App\Domains\Schedule\ValueObjects\Criteria;
use App\Domains\Schedule\ValueObjects\ScheduleIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'participants',
        'creator',
        'updater',
        'customer',
        'title',
        'description',
        'start',
        'end',
        'status',
        'repeat',
    ];

    /**
     * スケジュール識別子と一致するレコードを取得する.
     */
    public function scopeOfIdentifier(Builder $query, ScheduleIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件と一致するレコードを取得する.
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->status())) {
            $query->where('status', $criteria->status()->name);
        }

        if (!\is_null($criteria->date())) {
            $date = $criteria->date();

            if (!\is_null($date->start())) {
                $query->where('start', '>=', $date->start()->toAtomString());
            }

            if (!\is_null($date->end())) {
                $query->where('end', '<=', $date->end()->toAtomString());
            }
        }

        if (!\is_null($criteria->title())) {
            $query->where('title', 'like', "%{$criteria->title()}%");
        }

        if (!\is_null($criteria->user())) {
            $query->whereJsonContains('participants', $criteria->user()->value());
        }
    }
}
