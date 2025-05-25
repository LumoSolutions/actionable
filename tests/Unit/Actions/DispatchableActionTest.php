<?php

use Illuminate\Support\Facades\Queue;
use LumoSolutions\Actionable\AsyncActionJob;
use LumoSolutions\Actionable\Tests\Setup\Actions\DoDispatchableAction;
use LumoSolutions\Actionable\Traits\IsDispatchable;

describe('Dispatchable Actions', function () {
    beforeEach(function () {
        Queue::fake();
    });

    it('can dispatch DoDispatchableAction to the queue', function () {
        DoDispatchableAction::dispatch();

        Queue::assertPushed(AsyncActionJob::class);
    });

    it('can dispatch DoDispatchableAction with parameters', function () {
        DoDispatchableAction::dispatch('param1', 'param2');

        Queue::assertPushed(AsyncActionJob::class);
    });

    it('can dispatch DoDispatchableAction to a specific queue', function () {
        DoDispatchableAction::dispatchOn('high-priority');

        Queue::assertPushedOn('high-priority', AsyncActionJob::class);
    });

    it('can dispatch an action to the queue', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): string
            {
                return 'Action executed';
            }
        };

        $className = get_class($action);

        $className::dispatch();

        Queue::assertPushed(AsyncActionJob::class);
    });

    it('can dispatch an action with parameters', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle($param1, $param2): void {}
        };

        $className = get_class($action);

        $className::dispatch('hello', 'world');

        Queue::assertPushed(AsyncActionJob::class);
    });

    it('can dispatch an action to a specific queue', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): void {}
        };

        $className = get_class($action);

        $className::dispatchOn('high-priority');

        Queue::assertPushedOn('high-priority', AsyncActionJob::class);
    });

    it('can dispatch an action to a specific queue with parameters', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle($name, $data): void {}
        };

        $className = get_class($action);

        $className::dispatchOn('processing', 'test-name', ['key' => 'value']);

        Queue::assertPushedOn('processing', AsyncActionJob::class);
    });

    it('dispatches correct number of jobs', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): void {}
        };

        $className = get_class($action);

        $className::dispatch();
        $className::dispatch();
        $className::dispatchOn('test-queue');

        Queue::assertPushed(AsyncActionJob::class, 3);
    });

    it('handles variadic parameters correctly', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(...$items): void
            {
                // Action logic here
            }
        };

        $className = get_class($action);

        $className::dispatch('a', 'b', 'c', 'd');

        Queue::assertPushed(AsyncActionJob::class);
    });

    it('can dispatch multiple different action classes', function () {
        $action1 = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                // Action 1 logic
            }
        };

        $action2 = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                // Action 2 logic
            }
        };

        $class1 = get_class($action1);
        $class2 = get_class($action2);

        $class1::dispatch();
        $class2::dispatch();

        Queue::assertPushed(AsyncActionJob::class, 2);
    });

    it('maintains static context correctly', function () {
        $action1 = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                // Action logic
            }
        };

        $action2 = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                // Different action logic
            }
        };

        $class1 = get_class($action1);
        $class2 = get_class($action2);

        $class1::dispatch('param1');
        $class2::dispatch('param2');

        Queue::assertPushed(AsyncActionJob::class, 2);
    });

    it('can dispatch with complex parameter types', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle($string, $array, $object, $bool): void
            {
                // Action logic here
            }
        };

        $className = get_class($action);
        $testObject = (object) ['property' => 'value'];

        $className::dispatch(
            'test string',
            ['array' => 'data'],
            $testObject,
            true
        );

        Queue::assertPushed(AsyncActionJob::class);
    });

    it('dispatch returns void', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                // Action logic here
            }
        };

        $className = get_class($action);

        $result = $className::dispatch();

        expect($result)->toBeNull();
    });

    it('dispatchOn returns void', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                // Action logic here
            }
        };

        $className = get_class($action);

        $result = $className::dispatchOn('test-queue');

        expect($result)->toBeNull();
    });

    it('executes the action handle method when job runs', function () {
        $testContainer = new class
        {
            public bool $executed = false;
        };

        $action = new class($testContainer)
        {
            use IsDispatchable;

            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function handle(): void
            {
                $this->container->executed = true;
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $job = new AsyncActionJob($className, []);
        $job->handle();

        expect($testContainer->executed)->toBeTrue();
    });

    it('passes parameters to the action handle method', function () {
        $testContainer = new class
        {
            public array $receivedParams = [];
        };

        $action = new class($testContainer)
        {
            use IsDispatchable;

            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function handle($param1, $param2): void
            {
                $this->container->receivedParams = [$param1, $param2];
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $job = new AsyncActionJob($className, ['hello', 'world']);
        $job->handle();

        expect($testContainer->receivedParams)->toBe(['hello', 'world']);
    });

    it('handles DoDispatchableAction execution', function () {
        $job = new AsyncActionJob(DoDispatchableAction::class, []);
        $result = $job->handle();

        // The job handle method returns void, but we can verify it doesn't throw
        expect($result)->toBeNull();
    });

    it('executes action with complex parameters', function () {
        $testContainer = new class
        {
            public $receivedData = null;
        };

        $action = new class($testContainer)
        {
            use IsDispatchable;

            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function handle($name, $data, $flag): void
            {
                $this->container->receivedData = compact('name', 'data', 'flag');
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $testData = ['key' => 'value', 'items' => [1, 2, 3]];
        $job = new AsyncActionJob($className, ['test-name', $testData, true]);
        $job->handle();

        expect($testContainer->receivedData)->toBe([
            'name' => 'test-name',
            'data' => $testData,
            'flag' => true,
        ]);
    });

    it('executes action with variadic parameters', function () {
        $testContainer = new class
        {
            public array $receivedItems = [];
        };

        $action = new class($testContainer)
        {
            use IsDispatchable;

            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function handle(...$items): void
            {
                $this->container->receivedItems = $items;
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $job = new AsyncActionJob($className, ['a', 'b', 'c', 'd']);
        $job->handle();

        expect($testContainer->receivedItems)->toBe(['a', 'b', 'c', 'd']);
    });

    it('executes action that returns a value', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): string
            {
                return 'executed successfully';
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $job = new AsyncActionJob($className, []);

        // Job handle method doesn't return the action result, but should not throw
        $result = $job->handle();
        expect($result)->toBeNull();
    });

    it('propagates exceptions from action handle method', function () {
        $action = new class
        {
            use IsDispatchable;

            public function handle(): void
            {
                throw new \RuntimeException('Action failed');
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $job = new AsyncActionJob($className, []);

        expect(fn () => $job->handle())
            ->toThrow(\RuntimeException::class, 'Action failed');
    });

    it('creates action instance through Laravel container', function () {
        $testContainer = new class
        {
            public bool $containerCalled = false;
        };

        $action = new class($testContainer)
        {
            use IsDispatchable;

            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function handle(): void
            {
                // Action logic
            }
        };

        $className = get_class($action);

        // Mock the container call
        app()->bind($className, function () use ($action, $testContainer) {
            $testContainer->containerCalled = true;

            return $action;
        });

        $job = new AsyncActionJob($className, []);
        $job->handle();

        expect($testContainer->containerCalled)->toBeTrue();
    });

    it('has correct display name', function () {
        $job = new AsyncActionJob(DoDispatchableAction::class, []);

        expect($job->displayName())->toBe('Action: DoDispatchableAction');
    });

    it('has correct tags', function () {
        $job = new AsyncActionJob(DoDispatchableAction::class, []);

        expect($job->tags())->toBe([
            'async_action',
            'DoDispatchableAction',
        ]);
    });

    it('handles action with no parameters when empty array passed', function () {
        $testContainer = new class
        {
            public bool $executed = false;
        };

        $action = new class($testContainer)
        {
            use IsDispatchable;

            private $container;

            public function __construct($container)
            {
                $this->container = $container;
            }

            public function handle(): void
            {
                $this->container->executed = true;
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $job = new AsyncActionJob($className, []);
        $job->handle();

        expect($testContainer->executed)->toBeTrue();
    });
});
