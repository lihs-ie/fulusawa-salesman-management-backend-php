<?php

namespace Tests\Unit\Infrastructures\User;

use App\Domains\Common\ValueObjects\MailAddress;
use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\UserIdentifier;
use App\Exceptions\ConflictException;
use App\Infrastructures\Support\Common\EloquentCommonDomainDeflator;
use App\Infrastructures\User\EloquentUserRepository;
use App\Infrastructures\User\Models\User as Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Tests\Support\DependencyBuildable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;
use Tests\TestCase;

/**
 * @group unit
 * @group infrastructures
 * @group user
 *
 * @coversNothing
 *
 * @internal
 */
class EloquentUserRepositoryTest extends TestCase
{
    use EloquentCommonDomainDeflator;
    use DependencyBuildable;
    use FactoryResolvable;
    use RefreshDatabase;

    /**
     * テストに使用するレコード.
     */
    private ?Enumerable $records;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->records = clone $this->createRecords();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        $this->records = null;

        parent::tearDown();
    }

    /**
     * @testdox testAddSuccessPersistEntity addメソッドで新規のユーザーを永続化できること.
     */
    public function testAddSuccessPersistEntity(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $repository = $this->createRepository();

        $repository->add($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateIdentifier addメソッドで重複する識別子のユーザーを追加しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateIdentifier(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testAddFailureThrowsConflictExceptionWithDuplicateEmail addメソッドで重複するメールアドレスのユーザーを追加しようとすると例外が発生すること.
     */
    public function testAddFailureThrowsConflictExceptionWithDuplicateEmail(): void
    {
        $record = $this->pickRecord();

        $entity = $this->builder()->create(Entity::class, null, [
            'email' => $this->builder()->create(
                MailAddress::class,
                null,
                ['value' => $record->email]
            ),
        ]);

        $repository = $this->createRepository();

        $this->expectException(ConflictException::class);

        $repository->add($entity);
    }

    /**
     * @testdox testUpdateSuccessPersistEntity updateメソッドで既存のユーザーを更新できること.
     */
    public function testUpdateSuccessPersistEntity(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
            'identifier' => $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            ),
        ]);

        $repository = $this->createRepository();

        $repository->update($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier updateメソッドで存在しないユーザーを更新しようとすると例外が発生すること.
     */
    public function testUpdateFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->update($entity);
    }

    /**
     * @testdox testFindSuccessReturnsEntity ユーザーを取得できること.
     */
    public function testFindSuccessReturnsEntity(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(
            $this->builder()->create(
                UserIdentifier::class,
                null,
                ['value' => $record->identifier]
            )
        );

        $this->assertPropertyOf($actual);
    }

    /**
     * @testdox testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier 存在しないユーザーを取得しようとすると例外が発生すること.
     */
    public function testFindFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->find($this->builder()->create(UserIdentifier::class));
    }

    /**
     * @testdox testListSuccessReturnsEntities ユーザー一覧を取得できること.
     */
    public function testListSuccessReturnsEntities(): void
    {
        $repository = $this->createRepository();

        $actuals = $repository->list();

        $this->records
            ->zip($actuals)
            ->eachSpread(function ($record, $actual): void {
                $this->assertNotNull($record);
                $this->assertNotNull($actual);
                $this->assertInstanceOf(Entity::class, $actual);
                $this->assertPropertyOf($actual);
            })
        ;
    }

    /**
     * @testdox testDeleteSuccessRemoveEntity ユーザーを削除できること.
     */
    public function testDeleteSuccessRemoveEntity(): void
    {
        $target = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(new UserIdentifier($target->identifier));

        $this->assertDatabaseMissing('users', ['identifier' => $target->identifier]);
    }

    /**
     * @testdox testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier 存在しないユーザーを削除しようとすると例外が発生すること.
     */
    public function testDeleteFailureThrowsOutOfBoundsExceptionWithMissingIdentifier(): void
    {
        $repository = $this->createRepository();

        $this->expectException(\OutOfBoundsException::class);

        $repository->delete($this->builder()->create(UserIdentifier::class));
    }

    /**
     * テストに使用するレコードを生成するへルパ.
     */
    private function createRecords(): Enumerable
    {
        return $this->factory(Record::class)->createMany(\mt_rand(5, 10));
    }

    /**
     * リポジトリを生成するへルパ.
     */
    private function createRepository(): UserRepository
    {
        return new EloquentUserRepository(new Record());
    }

    /**
     * 生成済みのレコードから1件を取得する.
     */
    private function pickRecord(): Record
    {
        return $this->records->random();
    }

    /**
     * 永続化されたレコードの内容を比較する.
     */
    private function assertPersistedRecord(Entity $entity): void
    {
        $this->assertDatabaseHas('users', [
            'identifier' => $entity->identifier()->value(),
            'first_name' => $entity->firstName(),
            'last_name' => $entity->lastName(),
            'phone_number' => $this->deflatePhoneNumber($entity->phone()),
            'address' => $this->deflateAddress($entity->address()),
            'email' => $entity->email()->value(),
            'role' => $entity->role()->name,
        ]);
    }

    /**
     * レコードのプロパティを比較する.
     */
    private function assertPropertyOf(Entity $actual): void
    {
        $record = $this->records->first(
            fn (Record $record): bool => $record->identifier === $actual->identifier()->value()
        );

        $this->assertNotNull($record);
        $this->assertTrue($record->first_name === $actual->firstName());
        $this->assertTrue($record->last_name === $actual->lastName());
        $this->assertTrue($record->email === $actual->email()->value());
        $this->assertTrue($record->role === $actual->role()->name);
        $this->assertSame($record->phone_number, $this->deflatePhoneNumber($actual->phone()));
        $this->assertSame($record->address, $this->deflateAddress($actual->address()));
    }
}
