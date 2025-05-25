<?php

namespace LumoSolutions\Actionable\Tests\Setup\Actions;

use LumoSolutions\Actionable\Traits\IsRunnable;

class DoRunnableAction
{
    use IsRunnable;

    public function handle(): string
    {
        return 'Action executed successfully';
    }
}
