<?php

namespace Tests\Unit\Infrastructures\User;

use App\Domains\User\Entities\User as Entity;
use App\Domains\User\UserRepository;
use App\Domains\User\ValueObjects\UserIdentifier;
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
 */
class EloquentUserRepositoryTest extends TestCase
{
    use DependencyBuildable;
    use FactoryResolvable;
    use RefreshDatabase;

    /**
     * テストに使用するレコード.
     */
    private Enumerable|null $records;

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
     * @testdox testPersistSuccessOnCreate persistメソッドで新規のユーザーを永続化できること.
     */
    public function testPersistSuccessOnCreate(): void
    {
        $entity = $this->builder()->create(Entity::class);

        $repository = $this->createRepository();

        $repository->persist($entity);

        $this->assertPersistedRecord($entity);
    }

    /**
     * @testdox testPersistSuccessOnUpdate persistメソッドで既存のユーザーを更新できること.
     */
    public function testPersistSuccessOnUpdate(): void
    {
        $record = $this->pickRecord();

        $expected = $this->builder()->create(Entity::class, null, [
          'identifier' => new UserIdentifier($record->identifier)
        ]);

        $repository = $this->createRepository();

        $repository->persist($expected);

        $this->assertPersistedRecord($expected);
    }

    /**
     * @testdox testFindSuccess ユーザーを取得できること.
     */
    public function testFindSuccessReturnsInstance(): void
    {
        $record = $this->pickRecord();

        $repository = $this->createRepository();

        $actual = $repository->find(new UserIdentifier($record->identifier));

        $this->assertPropertyOf($actual);
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
          });
    }

    /**
     * @testdox testDeleteSuccess ユーザーを削除できること.
     */
    public function testDeleteSuccess(): void
    {
        $target = $this->pickRecord();

        $repository = $this->createRepository();

        $repository->delete(new UserIdentifier($target->identifier));

        $this->assertDatabaseMissing('users', ['identifier' => $target->identifier]);
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
          'postal_code_first' => $entity->address()->postalCode()->first(),
          'postal_code_second' => $entity->address()->postalCode()->second(),
          'prefecture' => $entity->address()->prefecture()->value,
          'city' => $entity->address()->city(),
          'street' => $entity->address()->street(),
          'building' => $entity->address()->building(),
          'phone_area_code' => $entity->phone()->areaCode(),
          'phone_local_code' => $entity->phone()->localCode(),
          'phone_subscriber_number' => $entity->phone()->subscriberNumber(),
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
        $this->assertTrue($record->phone_area_code === $actual->phone()->areaCode());
        $this->assertTrue($record->phone_local_code === $actual->phone()->localCode());
        $this->assertTrue($record->phone_subscriber_number === $actual->phone()->subscriberNumber());
        $this->assertTrue($record->postal_code_first === $actual->address()->postalCode()->first());
        $this->assertTrue($record->postal_code_second === $actual->address()->postalCode()->second());
        $this->assertTrue($record->prefecture === $actual->address()->prefecture()->value);
        $this->assertTrue($record->city === $actual->address()->city());
        $this->assertTrue($record->street === $actual->address()->street());
        $this->assertTrue($record->building === $actual->address()->building());
    }
}
