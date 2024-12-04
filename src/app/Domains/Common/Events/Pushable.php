<?php

namespace App\Domains\Common\Events;

/**
 * Illuminate\Events\Dispatcher::push に fire と同じく渡せるようにするためのtrait.
 */
trait Pushable
{
    public function __toString(): string
    {
        return static::class;
    }
}
