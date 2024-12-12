<?php

namespace App\Infrastructures\Authentication\Models;

use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Infrastructures\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class Authentication extends SanctumPersonalAccessToken
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    protected $primaryKey = 'identifier';

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'expires_at',
        'refresh_token',
        'refresh_token_expires_at',
        'abilities',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    /**
     * リレーション: ユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'tokenable_id');
    }

    /**
     * リレーション: tokenable.
     */
    public function tokenable()
    {
        return $this->belongsTo(User::class, 'tokenable_id');
    }

    /**
     * 認証識別子と一致するレコードを取得する.
     */
    public function scopeOfIdentifier(Builder $query, AuthenticationIdentifier $identifier): void
    {
        $query->where('identifier', $identifier->value());
    }

    /**
     * 指定されたトークンを持つレコードを取得する.
     */
    public function scopeOfToken(Builder $query, string $value, TokenType $type): void
    {
        if ($type === TokenType::ACCESS) {
            $query->where('token', hash('sha256', $value));
        } else {
            $query->where('refresh_token', hash('sha256', $value));
        }
    }
}
