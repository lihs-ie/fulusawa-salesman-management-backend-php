<?php

namespace App\Domains\Common\Events;

use Illuminate\Support\Collection;

/**
 * ドメインイベントを発行するクラスのインターフェース.
 */
interface Publisher
{
    /**
     * 発行されたドメインイベントのコレクションを返す.
     */
    public function events(): Collection;

    /**
     * 発行されたドメインイベントを空にする.
     */
    public function flushEvents(): void;
}
