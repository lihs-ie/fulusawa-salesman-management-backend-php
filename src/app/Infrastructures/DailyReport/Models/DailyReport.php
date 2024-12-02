<?php

namespace App\Infrastructures\DailyReport\Models;

use App\Domains\DailyReport\ValueObjects\Criteria;
use App\Domains\DailyReport\ValueObjects\DailyReportIdentifier;
use App\Infrastructures\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identifier',
        'user',
        'date',
        'schedules',
        'visits',
        'is_submitted',
        'updated_at',
    ];

    /**
     * リレーション: usersテーブル
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user', 'identifier');
    }

    /**
     * 日報識別子と一致するレコードを取得する
     */
    public function scopeOfIdentifier(Builder $query, DailyReportIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件と一致するレコードを取得する
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->user())) {
            $query->where('user', $criteria->user()->value());
        }

        if (!\is_null($criteria->date())) {
            $query->whereBetween(
                'date',
                [$criteria->date()->start()?->toDateString(), $criteria->date()->end()?->toDateString()]
            );
        }

        if (!\is_null($criteria->isSubmitted())) {
            $query->where('is_submitted', $criteria->isSubmitted());
        }
    }
}
