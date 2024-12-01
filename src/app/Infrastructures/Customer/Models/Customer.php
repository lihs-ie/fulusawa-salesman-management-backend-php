<?php

namespace App\Infrastructures\Customer\Models;

use App\Domains\Customer\ValueObjects\Criteria;
use App\Domains\Customer\ValueObjects\CustomerIdentifier;
use App\Infrastructures\Cemetery\Models\Cemetery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * customersテーブル
 */
class Customer extends Model
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
        'first_name',
        'last_name',
        'phone_area_code',
        'phone_local_code',
        'phone_subscriber_number',
        'postal_code_first',
        'postal_code_second',
        'prefecture',
        'city',
        'street',
        'building',
        'cemeteries',
        'transaction_histories',
    ];

    /**
     * リレーション: cemeteriesテーブル
     */
    public function cemeteries(): HasMany
    {
        return $this->hasMany(Cemetery::class);
    }

    /**
     * 顧客識別子に一致するレコードを取得する
     */
    public function scopeOfIdentifier(Builder $query, CustomerIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件に一致するレコードを取得する
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->name())) {
            $query
                ->orWhere('first_name', 'like', "%{$criteria->name()}%")
                ->orWhere('last_name', 'like', "%{$criteria->name()}%");
        }

        if (!\is_null($criteria->phone())) {
            $query
                ->where('phone_area_code', $criteria->phone()->areaCode())
                ->where('phone_local_code', $criteria->phone()->localCode())
                ->where('phone_subscriber_number', $criteria->phone()->subscriberNumber());
        }

        if (!\is_null($criteria->postalCode())) {
            $query
                ->where('postal_code_first', $criteria->postalCode()->first())
                ->where('postal_code_second', $criteria->postalCode()->second());
        }
    }
}
