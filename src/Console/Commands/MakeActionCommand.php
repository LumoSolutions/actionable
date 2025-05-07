<?php

namespace LumoSolutions\Actionable\Console\Commands;

use LumoSolutions\Actionable\Console\BaseStubCommand;

class MakeActionCommand extends BaseStubCommand
{
    protected $signature = 'make:action {name} {--invokable : Generate an invokable action}';

    protected $description = 'Create a new Action class';

    protected function subDirectory(): string
    {
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
        ];
    }

    protected function getStubPath(array $options): string
    {
        $stubName = $options['invokable']
            ? 'action.invokable.stub'
            : 'action.stub';

        return $this->resolveStubPath($stubName);
    }
}
