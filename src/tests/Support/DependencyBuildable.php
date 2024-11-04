<?php

namespace Tests\Support;

/**
 * テスト用のインスタンスを生成する機能へのブリッジ.
 */
trait DependencyBuildable
{
    /**
     * ビルダを取得する.
     */
    protected function builder(): DependencyBuilder
    {
        return DependencyBuilder::getInstance('App\\', 'Tests\\Support\\Factories');
    }
}
