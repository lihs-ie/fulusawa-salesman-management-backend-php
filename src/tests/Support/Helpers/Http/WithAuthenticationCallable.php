<?php

namespace Tests\Support\Helpers\Http;

use App\Domains\User\ValueObjects\Role;
use App\Infrastructures\Authentication\Models\Authentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;

/**
 * 認証付きのリクエストを生成するためのへルパクラス.
 */
trait WithAuthenticationCallable
{
    use FactoryResolvable;
    use RefreshDatabase;

    /**
     * ユーザー権限を提供するプロパイダ.
     */
    public static function provideRole(): \Generator
    {
        yield 'admin' => [Role::ADMIN];

        yield 'user' => [Role::USER];
    }

    /**
     * 認証情報を付与してAPIを実行する.
     */
    protected function callAPIWithAuthentication(\Closure $callback, Role $role = Role::ADMIN): TestResponse
    {
        $record = $this->factory(Authentication::class)
            ->roleOf($role)
            ->create()
            ->with('user')
            ->first()
        ;

        $authentication = $this->json(
            method: 'POST',
            uri: 'api/auth/login',
            data: [
                'identifier' => Uuid::uuid7()->toString(),
                'email' => $record->user->email,
                'password' => $record->user->password,
            ]
        );

        return $callback($authentication['accessToken']['value']);
    }
}
