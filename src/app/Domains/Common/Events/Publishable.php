<?php

namespace App\Domains\Common\Events;

use Illuminate\Support\Collection;

/**
 * ドメインイベントを発行する機能（Publisherインターフェースのデフォルト実装）.
 */
trait Publishable
{
    /**
     * 発行されたドメインイベントのコレクション.
     *
     * @var Collection
     */
    private $pool;

    /**
     * 発行されたドメインイベントのコレクションを返す.
     */
    public function events(): Collection
    {
        return clone $this->pool();
    }

    /**
     * 発行されたドメインイベントを空にする.
     */
    public function flushEvents(): void
    {
        $this->pool = new Collection();
    }

    /**
     * イベントを発行する.
     */
    protected function publish($event): void
    {
        $this->pool()->push($event);
    }

    /**
     * イベントプールを取得する.
     */
    protected function pool(): Collection
    {
        if (is_null($this->pool)) {
            $this->pool = new Collection();
        }

        return $this->pool;
    }
}
