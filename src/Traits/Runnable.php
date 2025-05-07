<?php

namespace LumoSolutions\Actionable\Traits;

trait Runnable
{
    public static function run(...$params): mixed
    {
        // Create a new instance of the class using Laravel's container
        // to support dependency injection, then call the execute method
        $instance = app(static::class);
        return $instance->execute(...$params) ?? null;
    }
}
