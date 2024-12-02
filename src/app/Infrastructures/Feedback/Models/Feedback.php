<?php

namespace App\Infrastructures\Feedback\Models;

use App\Domains\Feedback\ValueObjects\Criteria;
use App\Domains\Feedback\ValueObjects\Criteria\Sort;
use App\Domains\Feedback\ValueObjects\FeedbackIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'feedbacks';

    protected $fillable = [
        'identifier',
        'type',
        'status',
        'content',
        'created_at',
        'updated_at',
    ];

    /**
     * フィードバック識別子と一致するレコードを取得する.
     */
    public function scopeOfIdentifier(Builder $query, FeedbackIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件と一致するレコードを取得する.
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->type())) {
            $query->where('type', $criteria->type()->name);
        }

        if (!\is_null($criteria->status())) {
            $query->where('status', $criteria->status()->name);
        }

        if (!\is_null($criteria->sort())) {
            match ($criteria->sort()) {
                Sort::CREATED_AT_ASC => $query->orderBy('created_at', 'asc'),
                Sort::CREATED_AT_DESC => $query->orderBy('created_at', 'desc'),
                Sort::UPDATED_AT_ASC => $query->orderBy('updated_at', 'asc'),
                Sort::UPDATED_AT_DESC => $query->orderBy('updated_at', 'desc'),
            };
        }
    }
}
