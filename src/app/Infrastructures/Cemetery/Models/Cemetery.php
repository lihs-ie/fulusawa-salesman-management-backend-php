<?php

namespace App\Infrastructures\Cemetery\Models;

use App\Domains\Cemetery\ValueObjects\Criteria;
use App\Infrastructures\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cemetery extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'customer',
        'name',
        'type',
        'construction',
        'in_house',
        'updated_at',
    ];

    /**
     * リレーション: customersテーブル
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 検索条件に一致するレコードを取得する
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria)
    {
        if ($criteria->customer) {
            $query->where('customer', $criteria->customer->value);
        }
    }
}
