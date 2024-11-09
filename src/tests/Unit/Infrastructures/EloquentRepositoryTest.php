<?php

namespace Tests\Unit\Infrastructures;

use Illuminate\Database\Eloquent\Model as Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Enumerable;
use Tests\Support\Helpers\Infrastructures\Database\FactoryResolvable;

/**
 * eloquentを使用するリポジトリのテスト
 */
trait EloquentRepositoryTest
{
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
     * 生成済みのレコードから1件を取得する.
     */
    protected function pickRecord(): Record
    {
        return $this->records->random();
    }

    /**
     * テストに使用するレコードを生成するへルパ.
     */
    abstract protected function createRecords(): Enumerable;
}
