<?php

namespace LumoSolutions\Actionable\Console\Commands;

use LumoSolutions\Actionable\Console\BaseStubCommand;

class MakeActionCommand extends BaseStubCommand
{
    protected $signature = 'make:action {name}
                           {--invokable : Generate an invokable action}
                           {--dispatchable : Generate a dispatchable action}';

    protected $description = 'Create a new Action class';

    protected function subDirectory(): string {
        return 'Actions';
    }

    protected function getTypeDescription(): string
    {
        return 'Action';
    }

    protected function getStubOptions(): array
    {
        return [
            'invokable' => $this->option('invokable') ?? false,
            'dispatchable' => $this->option('dispatchable') ?? false,
        ];
    }

    protected function getStubPath(array $options): string
    {
        if ($options['dispatchable']) {
            $stubName = 'action.dispatchable.stub';
        } elseif ($options['invokable']) {
            $stubName = 'action.invokable.stub';
        } else {
            $stubName = 'action.stub';
        }

        return $this->resolveStubPath($stubName);
    }
}
