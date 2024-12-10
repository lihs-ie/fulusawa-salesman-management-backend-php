<?php

namespace Tests\Unit\Infrastructures\Authentication;

use App\Domains\Authentication\AuthenticationRepository;
use App\Domains\Authentication\Entities\Authentication as Entity;
use App\Domains\Authentication\ValueObjects\AuthenticationIdentifier;
use App\Domains\Authentication\ValueObjects\Token;
use App\Domains\Authentication\ValueObjects\TokenType;
use App\Domains\User\ValueObjects\Role;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\InvalidTokenException;
use App\Infrastructures\Authentication\EloquentAuthenticationRepository;
use App\Infrastructures\Authentication\Models\Authentication as Record;
use App\Infrastructures\User\Models\User as UserRecord;
use Carbon\CarbonImmutable;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\DependencyBuildable;
use Tests\TestCase;
use Tests\Unit\Infrastructures\EloquentRepositoryTest;

/**
 * @group unit
 * @group infrastructures
 * @group authentication
 *
 * @coversNothing
 *
 * @internal
 */
class EloquentAuthenticationRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use EloquentRepositoryTest;

    /**
     * テストに使用するレコード.
     */
    private ?Enumerable $records;

    /**
     * テストで使用するハッシュソルト.
     */
    private ?string $hashSalt;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->hashSalt = Str::random(\mt_rand(1, 255));
        $this->records = clone $this->createRecords();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        $this->hashSalt = null;
        $this->records = null;

        parent::tearDown();
    }

    /**
     * @testdox testPersistSuccessOnCreate persistメソッドで新規の認証情報を永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $user = $this->createUserRecord();

        $identifier = $this->builder()->create(AuthenticationIdentifier::class);

        $repository = $this->createRepository();

        $role = match ($user->role) {
            Role::ADMIN->name => Role::ADMIN,
            Role::USER->name => Role::USER,
        };

        $repository->persist(
            $identifier,
            $this->builder()->create(
                class: UserIdentifier::class,
                overrides: ['value' => $user->identifier]
            ),
            role: $role
        );

        $this->assertDatabaseHas('authentications', [
            'identifier' => $identifier->value(),
        ]);
    }

    /**
     * @testdox testFindSuccessfulReturnsEntity findメソッドで認証情報を取得できること.
     */
    public function testFindSuccessfulReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $identifier = $this->builder()->create(AuthenticationIdentifier::class, null, [
            'value' => $record->identifier,
        ]);

        $repository = $this->createRepository();

        $actual = $repository->find($identifier);

        $this->assertInstanceOf(Entity::class, $actual);
        $this->assertTrue($identifier->equals($actual->identifier));
    }

    /**
     * @testdox testFindThrowsWhenMissing findメソッドで存在しない認証情報を取得しようとすると例外を投げること.
     */
    public function testFindThrowsWhenMissing(): void
    {
        $identifier = $this->builder()->create(AuthenticationIdentifier::class);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->find($identifier);
    }

    /**
     * @testdox testIntrospectionSuccessfulReturnsTrueWithValidToken introspectionメソッドに有効なトークンを与えた時trueを返すこと.
     */
    public function testIntrospectionSuccessfulReturnsTrueWithValidToken(): void
    {
        $record = $this->factory(Record::class)->bothValid()->create();

        $repository = $this->createRepository();

        $accessToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::ACCESS,
            'value' => $record->token,
            'expiresAt' => CarbonImmutable::parse($record->expires_at),
        ]);

        $refreshToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::REFRESH,
            'value' => $record->refresh_token,
            'expiresAt' => CarbonImmutable::parse($record->refresh_token_expires_at),
        ]);

        $this->assertTrue($repository->introspection($accessToken->value(), $accessToken->type()));
        $this->assertTrue($repository->introspection($refreshToken->value(), $refreshToken->type()));
    }

    /**
     * @testdox testIntrospectionSuccessfulReturnsFalseWithExpiredToken introspectionメソッドに有効期限切れのトークンを与えた時falseを返すこと.
     */
    public function testIntrospectionSuccessfulReturnsFalseWithExpiredToken(): void
    {
        $record = $this->factory(Record::class)->bothExpired()->create();

        $repository = $this->createRepository();

        $accessToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::ACCESS,
            'value' => $record->token,
            'expiresAt' => CarbonImmutable::parse($record->expires_at),
        ]);

        $refreshToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::REFRESH,
            'value' => $record->refresh_token,
            'expiresAt' => CarbonImmutable::parse($record->refresh_token_expires_at),
        ]);

        $this->assertFalse($repository->introspection($accessToken->value(), $accessToken->type()));
        $this->assertFalse($repository->introspection($refreshToken->value(), $refreshToken->type()));
    }

    /**
     * @testdox testRefreshSuccessfulReturnsRefreshedAuthentication refreshメソッドで認証情報を更新できること.
     */
    public function testRefreshSuccessfulReturnsRefreshedAuthentication(): void
    {
        $record = $this->factory(Record::class)->roleOf(Role::ADMIN)->bothValid()->create();

        $repository = $this->createRepository();

        $refreshToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::REFRESH,
            'value' => $record->refresh_token,
            'expiresAt' => CarbonImmutable::parse($record->refresh_token_expires_at),
        ]);

        $actual = $repository->refresh($refreshToken->value(), $refreshToken->type());

        $this->assertInstanceOf(Entity::class, $actual);
        $this->assertFalse(Hash::check($actual->accessToken()->value(), $record->token));
        $this->assertFalse(Hash::check($actual->refreshToken()->value(), $record->refresh_token));

        $this->assertDatabaseHas('authentications', [
            'identifier' => $actual->identifier()->value(),
            'tokenable_id' => $actual->user()->value(),
            'expires_at' => $actual->accessToken()->expiresAt()->toAtomString(),
            'refresh_token_expires_at' => $actual->refreshToken()->expiresAt()->toAtomString(),
            'abilities' => \json_encode([Role::ADMIN->name]),
        ]);
    }

    /**
     * @testdox testRefreshThrowsUnexpectedExceptionWithAccessToken refreshメソッドでアクセストークンを与えた時例外を投げること.
     */
    public function testRefreshThrowsUnexpectedExceptionWithAccessToken(): void
    {
        $record = $this->factory(Record::class)->bothValid()->create();

        $repository = $this->createRepository();

        $accessToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::ACCESS,
            'value' => $record->token,
            'expiresAt' => CarbonImmutable::parse($record->expires_at),
        ]);

        $this->expectException(InvalidTokenException::class);

        $repository->refresh($accessToken->value(), $accessToken->type());
    }

    /**
     * @testdox testRefreshThrowsWhenMissing refreshメソッドで存在しないトークンを与えた時例外を投げること.
     */
    public function testRefreshThrowsWhenMissing(): void
    {
        $repository = $this->createRepository();

        $refreshToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::REFRESH,
        ]);

        $this->expectException(InvalidTokenException::class);

        $repository->refresh($refreshToken->value(), $refreshToken->type());
    }

    /**
     * @testdox testRevokeSuccessful revokeメソッドでトークンを無効化できること.
     */
    public function testRevokeSuccessful(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $accessToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::ACCESS,
            'value' => $record->token,
        ]);

        $refreshToken = $this->builder()->create(Token::class, null, [
            'type' => TokenType::REFRESH,
            'value' => $record->refresh_token,
        ]);

        $repository->revoke($accessToken->value(), $accessToken->type());
        $repository->revoke($refreshToken->value(), $refreshToken->type());

        $this->assertDatabaseHas('authentications', [
            'identifier' => $record->identifier,
            'token' => null,
            'expires_at' => null,
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * リポジトリを生成するへルパ.
     */
    private function createRepository(): AuthenticationRepository
    {
        return new EloquentAuthenticationRepository(
            new Record(),
            \mt_rand(1, 10),
            \mt_rand(1, 10),
            Str::random(\mt_rand(1, 255)),
            $this->hashSalt
        );
    }

    /**
     * ユーザーレコードを生成する.
     */
    private function createUserRecord(): UserRecord
    {
        return $this->factory(UserRecord::class)->create();
    }
}
