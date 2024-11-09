<?php

namespace App\Infrastructures\Customer\Models;

use App\Infrastructures\Cemetery\Models\Cemetery;
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
}
