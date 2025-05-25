<?php

namespace LumoSolutions\Actionable\Tests\Setup\Actions;

use LumoSolutions\Actionable\Traits\IsDispatchable;

class DoDispatchableAction
{
    use IsDispatchable;

    public function handle(): string
    {
        return 'Action dispatched successfully';
    }
}
