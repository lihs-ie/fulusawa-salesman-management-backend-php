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
 * customersテーブル.
 */
class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $primaryKey = 'identifier';

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
        'phone_number',
        'address',
        'cemeteries',
        'transaction_histories',
    ];

    /**
     * リレーション: cemeteriesテーブル.
     */
    public function cemeteries(): HasMany
    {
        return $this->hasMany(Cemetery::class);
    }

    /**
     * 顧客識別子に一致するレコードを取得する.
     */
    public function scopeOfIdentifier(Builder $query, CustomerIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 検索条件に一致するレコードを取得する.
     */
    public function scopeOfCriteria(Builder $query, Criteria $criteria): void
    {
        if (!\is_null($criteria->name())) {
            $query
                ->orWhere('first_name', 'like', "%{$criteria->name()}%")
                ->orWhere('last_name', 'like', "%{$criteria->name()}%")
            ;
        }

        if (!\is_null($criteria->phone())) {
            $query
                ->where('phone_number->areaCode', $criteria->phone()->areaCode())
                ->where('phone_number->localCode', $criteria->phone()->localCode())
                ->where('phone_number->subscriberNumber', $criteria->phone()->subscriberNumber())
            ;
        }

        if (!\is_null($criteria->postalCode())) {
            $postalCode = $criteria->postalCode();

            $query
                ->where('address->postalCode->first', $postalCode->first())
                ->where('address->postalCode->second', $postalCode->second())
            ;
        }
    }
}
