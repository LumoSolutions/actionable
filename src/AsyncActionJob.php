<?php

namespace LumoSolutions\Actionable;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AsyncActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $actionClass;

    protected array $params;

    public function __construct(string $actionClass, array $params = [])
    {
        $this->actionClass = $actionClass;
        $this->params = $params;
    }

    public function handle(): void
    {
        $action = app($this->actionClass);
        $action->handle(...$this->params);
    }

    public function displayName(): string
    {
        return sprintf('Action: %s', class_basename($this->actionClass));
    }

    public function tags(): array
    {
        return [
            'async_action',
            class_basename($this->actionClass),
        ];
    }
}
