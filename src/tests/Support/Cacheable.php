<?php

namespace Tests\Support;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository as CacheRepository;

/**
 * キャッシュリポジトリを生成するトレイト.
 */
trait Cacheable
{
    /**
     * キャッシュを行わないキャッシュリポジトリを生成するヘルパ.
     */
    protected function createNullCacheRepository(): CacheRepository
    {
        return new CacheRepository(new NullStore());
    }

    /**
     * キャッシュを行うキャッシュリポジトリを生成するヘルパ.
     */
    protected function createCacheRepository(): CacheRepository
    {
        return new CacheRepository(new ArrayStore());
    }
}
