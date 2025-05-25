<?php

use LumoSolutions\Actionable\Tests\Setup\Actions\DoRunnableAction;
use LumoSolutions\Actionable\Traits\IsRunnable;

describe('Runnable Actions', function () {
    it('can be executed using the run method', function () {
        $result = DoRunnableAction::run();

        expect($result)->toBe('Action executed successfully');
    });

    it('creates a new instance through Laravel container', function () {
        $result = DoRunnableAction::run();

        expect($result)->toBeString()
            ->and($result)->not()->toBeEmpty();
    });

    it('passes parameters to the handle method', function () {
        $action = new class
        {
            use IsRunnable;

            public function handle($param1, $param2): string
            {
                return "Params: {$param1}, {$param2}";
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);
        $result = $className::run('hello', 'world');

        expect($result)->toBe('Params: hello, world');
    });

    it('handles actions with no parameters', function () {
        $result = DoRunnableAction::run();

        expect($result)->toBeString()
            ->and($result)->not()->toBeEmpty();
    });

    it('supports dependency injection through Laravel container', function () {
        $dependency = new class
        {
            public function process(): string
            {
                return 'processed';
            }
        };

        $action = new class($dependency)
        {
            use IsRunnable;

            private $dependency;

            public function __construct($dependency)
            {
                $this->dependency = $dependency;
            }

            public function handle(): string
            {
                return $this->dependency->process();
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);
        $result = $className::run();

        expect($result)->toBe('processed');
    });

    it('returns mixed types from handle method', function () {
        $stringAction = new class
        {
            use IsRunnable;

            public function handle(): string
            {
                return 'string result';
            }
        };

        $arrayAction = new class
        {
            use IsRunnable;

            public function handle(): array
            {
                return ['key' => 'value'];
            }
        };

        $boolAction = new class
        {
            use IsRunnable;

            public function handle(): bool
            {
                return true;
            }
        };

        app()->bind(get_class($stringAction), fn () => $stringAction);
        app()->bind(get_class($arrayAction), fn () => $arrayAction);
        app()->bind(get_class($boolAction), fn () => $boolAction);

        expect(get_class($stringAction)::run())->toBe('string result')
            ->and(get_class($arrayAction)::run())->toBe(['key' => 'value'])
            ->and(get_class($boolAction)::run())->toBe(true);
    });

    it('can handle complex parameter combinations', function () {
        $action = new class
        {
            use IsRunnable;

            public function handle(string $name, array $data = [], bool $flag = false): array
            {
                return [
                    'name' => $name,
                    'data' => $data,
                    'flag' => $flag,
                ];
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $result = $className::run('test', ['item' => 'value'], true);

        expect($result)->toBe([
            'name' => 'test',
            'data' => ['item' => 'value'],
            'flag' => true,
        ]);
    });

    it('handles exceptions thrown in handle method', function () {
        $action = new class
        {
            use IsRunnable;

            public function handle(): void
            {
                throw new \Exception('Something went wrong');
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        expect(fn () => $className::run())
            ->toThrow(\Exception::class, 'Something went wrong');
    });

    it('maintains static context correctly', function () {
        expect(DoRunnableAction::run())->toBe('Action executed successfully');

        $action1 = new class
        {
            use IsRunnable;

            public function handle(): string
            {
                return 'action1';
            }
        };

        $action2 = new class
        {
            use IsRunnable;

            public function handle(): string
            {
                return 'action2';
            }
        };

        $class1 = get_class($action1);
        $class2 = get_class($action2);

        app()->bind($class1, fn () => $action1);
        app()->bind($class2, fn () => $action2);

        expect($class1::run())->toBe('action1')
            ->and($class2::run())->toBe('action2');
    });

    it('can handle null return values', function () {
        $action = new class
        {
            use IsRunnable;

            public function handle(): null
            {
                return null;
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $result = $className::run();

        expect($result)->toBeNull();
    });

    it('works with variadic parameters', function () {
        $action = new class
        {
            use IsRunnable;

            public function handle(...$items): array
            {
                return $items;
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);
        $result = $className::run('a', 'b', 'c', 'd');

        expect($result)->toBe(['a', 'b', 'c', 'd']);
    });
});
