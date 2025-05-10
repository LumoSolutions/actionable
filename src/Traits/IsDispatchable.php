<?php

namespace LumoSolutions\Actionable\Traits;

use LumoSolutions\Actionable\AsyncActionJob;

trait IsDispatchable
{
    public static function dispatch(...$params): void
    {
        AsyncActionJob::dispatch(static::class, $params);
    }

    public static function dispatchOn(string $queue, ...$params): void
    {
        AsyncActionJob::dispatch(static::class, $params)->onQueue($queue);
    }
}
