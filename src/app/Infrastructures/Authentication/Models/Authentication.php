<?php

namespace App\Infrastructures\Authentication\Models;

use App\Infrastructures\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class Authentication extends SanctumPersonalAccessToken
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'tokenable_id',
        'tokenable_type',
        'name',
        'access_token',
        'access_token_expires_at',
        'refresh_token',
        'refresh_token_expires_at',
        'abilities',
        'last_used_at',
    ];

    /**
     * リレーション: ユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'tokenable_id');
    }
}
