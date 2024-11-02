<?php

namespace Tests\Unit\UseCases;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

/**
 * 永続化を行うユースケースの基底テスト
 */
trait PersistUseCaseTest
{
    /**
     * 永続化内容のテストに使用するハンドラを生成するへルパ.
     */
    protected function createPersistHandler(): array
    {
        $persisted = new Collection();

        $onPersist = function ($instance) use ($persisted): void {
            $key = \get_class($instance);

            if (!$persisted->has($key)) {
                $persisted->put($key, new Collection());
            }

            $persisted->get($key)->push($instance);
        };

        return [$persisted, $onPersist];
    }

    /**
     * 削除内容のテストに使用するハンドラを生成するへルパ.
     */
    protected function createRemoveHandler(): array
    {
        $removed = new Collection();

        $onRemove = function (Enumerable $instances) use ($removed): void {
            $instances->each(fn ($instance) => $removed->push($instance));
        };

        return [$removed, $onRemove];
    }

    /**
     * 永続化内容を比較するへルパ.
     */
    protected function assertPersisted($expected, Enumerable $persisted, string $class): void
    {
        $this->assertSame(1, $persisted->get($class)->count());

        $actual = $persisted->get($class)->first();
        $this->assertInstanceOf($class, $actual);
        $this->assertEntity($expected, $actual);
    }

    /**
     * 永続化を行うユースケースを生成するへルパ.
     */
    abstract protected function createPersistUseCase();

    /**
     * 永続化を行う空のユースケースを生成するへルパ.
     */
    abstract protected function createEmptyPersistedUseCase();

    /**
     * テスト対象が扱うエンティティを比較するへルパ.
     */
    abstract protected function assertEntity($expected, $actual): void;
}
