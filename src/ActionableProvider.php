<?php

namespace LumoSolutions\Actionable;

use Illuminate\Support\ServiceProvider;
use LumoSolutions\Actionable\Console\BaseStubCommand;
use LumoSolutions\Actionable\Console\Commands\MakeActionCommand;
use LumoSolutions\Actionable\Console\Commands\MakeDtoCommand;

class ActionableProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
                MakeDtoCommand::class,
            ]);

            $this->publishes(
                [BaseStubCommand::packageStubBasePath() => BaseStubCommand::applicationStubBasePath()],
                'actionable-stubs'
            );
        }
    }
}
