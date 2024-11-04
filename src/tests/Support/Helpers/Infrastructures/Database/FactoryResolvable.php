<?php

namespace Tests\Support\Helpers\Infrastructures\Database;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Laravel 8.x以降のデータベースファクトリを使用するためのヘルパ.
 */
trait FactoryResolvable
{
    /**
     * 初期化.
     */
    protected function setUpFactoryResolvable(): void
    {
        Factory::guessModelNamesUsing(function (Factory $factory): string {
            $factoryName = Str::replaceLast('Factory', '', Str::replaceFirst($this->factoryNamespace(), '', \get_class($factory)));
            $fragments = explode('\\', $factoryName, 2);

            if (count($fragments) < 2) {
                return $this->applicationNamespace() . 'Models\\' . $factoryName;
            }

            [$domain, $model] = $fragments;

            return $this->applicationNamespace() . 'Infrastructures\\' . $domain . '\\Models\\' . $model;
        });

        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            $trimmed = Str::replaceFirst($this->applicationNamespace(), '', $modelName);

            if (Str::startsWith($trimmed, 'Infrastructures\\')) {
                [$domain,, $model] = explode('\\', Str::replaceFirst('Infrastructures\\', '', $trimmed));

                return $this->factoryNamespace() . $domain . '\\' . $model . 'Factory';
            }

            if (Str::startsWith($trimmed, 'Models\\')) {
                return $this->factoryNamespace() . Str::replaceFirst('Models\\', '', $trimmed);
            }

            return $modelName . 'Factory';
        });
    }

    /**
     * Eloquentモデルクラスに対応するデータベースファクトリを生成する.
     *
     * @param string $model 対象となるモデルクラスのFQCN
     */
    protected function factory(string $model): Factory
    {
        return Factory::factoryForModel($model);
    }

    /**
     * ファクトリクラスのルート名前空間.
     */
    protected function factoryNamespace(): string
    {
        return 'Database\\Factories\\';
    }

    /**
     * アプリケーションのルート名前空間.
     */
    protected function applicationNamespace(): string
    {
        if (isset($this->app)) {
            return 'App\\';
        }

        return '';
    }
}
