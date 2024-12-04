<?php

namespace App\Infrastructures\TransactionHistory\Models;

use App\Domains\TransactionHistory\ValueObjects\Criteria;
use App\Domains\TransactionHistory\ValueObjects\Criteria\Sort;
use App\Domains\TransactionHistory\ValueObjects\TransactionHistoryIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $primaryKey = 'identifier';

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'customer',
        'user',
        'type',
        'description',
        'date',
    ];

    /**
     * 取引履歴識別子に一致するレコードを取得する.
     */
    public function scopeOfIdentifier(Builder $query, TransactionHistoryIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件に一致するレコードを取得する.
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->user())) {
            $query->where('user', $criteria->user()->value());
        }

        if (!\is_null($criteria->customer())) {
            $query->where('customer', $criteria->customer()->value());
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
