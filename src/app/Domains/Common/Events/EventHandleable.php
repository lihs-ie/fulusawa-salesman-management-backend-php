<?php

namespace App\Domains\Common\Events;

use Illuminate\Events\Dispatcher;

/**
 * ドメインイベントとリスナを管理する機能.
 */
trait EventHandleable
{
    /**
     * イベントディスパッチャ.
     *
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * イベントリスナを登録する.
     */
    public function listen(string $eventName, callable $listener): void
    {
        $this->dispatcher()->listen($eventName, $listener);
    }

    /**
     * 対象のインスタンスが保持しているイベントを発行する.
     */
    protected function dispatch(Publisher $publisher): void
    {
        $dispatcher = $this->dispatcher();

        $publisher->events()->each(function ($event) use ($dispatcher): void {
            $dispatcher->dispatch($event);
        });

        $publisher->flushEvents();
    }

    /**
     * イベントディスパッチャを取得する.
     */
    protected function dispatcher(): Dispatcher
    {
        if (is_null($this->dispatcher)) {
            $this->dispatcher = new Dispatcher();
        }

        return $this->dispatcher;
    }
}
