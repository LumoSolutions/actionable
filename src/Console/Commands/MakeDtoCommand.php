<?php

namespace LumoSolutions\Actionable\Console\Commands;

use LumoSolutions\Actionable\Console\BaseStubCommand;

class MakeDtoCommand extends BaseStubCommand
{
    protected $signature = 'make:dto {name}';

    protected $description = 'Create a new DTO class';

    protected function subDirectory(): string
    {
        return 'Dtos';
    }

    protected function getTypeDescription(): string
    {
        return 'DTO';
    }

    protected function getStubPath(array $options): string
    {
        return $this->resolveStubPath('/dto.stub');
    }
}
