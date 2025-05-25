<?php

describe('Invokable Actions', function () {
    it('can be invoked as a function', function () {
        $action = new class
        {
            public function __invoke(): string
            {
                return 'Action invoked successfully';
            }
        };

        $result = $action();

        expect($result)->toBe('Action invoked successfully');
    });

    it('can be invoked with parameters', function () {
        $action = new class
        {
            public function __invoke($param1, $param2): string
            {
                return "Params: {$param1}, {$param2}";
            }
        };

        $result = $action('hello', 'world');

        expect($result)->toBe('Params: hello, world');
    });

    it('can return different types', function () {
        $stringAction = new class
        {
            public function __invoke(): string
            {
                return 'string result';
            }
        };

        $arrayAction = new class
        {
            public function __invoke(): array
            {
                return ['key' => 'value'];
            }
        };

        $boolAction = new class
        {
            public function __invoke(): bool
            {
                return true;
            }
        };

        $intAction = new class
        {
            public function __invoke(): int
            {
                return 42;
            }
        };

        expect($stringAction())->toBe('string result')
            ->and($arrayAction())->toBe(['key' => 'value'])
            ->and($boolAction())->toBe(true)
            ->and($intAction())->toBe(42);
    });

    it('can handle complex parameter combinations', function () {
        $action = new class
        {
            public function __invoke(string $name, array $data = [], bool $flag = false): array
            {
                return [
                    'name' => $name,
                    'data' => $data,
                    'flag' => $flag,
                ];
            }
        };

        $result = $action('test', ['item' => 'value'], true);

        expect($result)->toBe([
            'name' => 'test',
            'data' => ['item' => 'value'],
            'flag' => true,
        ]);
    });

    it('can handle variadic parameters', function () {
        $action = new class
        {
            public function __invoke(...$items): array
            {
                return $items;
            }
        };

        $result = $action('a', 'b', 'c', 'd');

        expect($result)->toBe(['a', 'b', 'c', 'd']);
    });

    it('can be invoked through Laravel container', function () {
        $action = new class
        {
            public function __invoke(): string
            {
                return 'Container invoked';
            }
        };

        $className = get_class($action);
        app()->bind($className, fn () => $action);

        $instance = app($className);
        $result = $instance();

        expect($result)->toBe('Container invoked');
    });

    it('supports dependency injection', function () {
        $dependency = new class
        {
            public function process(): string
            {
                return 'processed';
            }
        };

        $action = new class($dependency)
        {
            private $dependency;

            public function __construct($dependency)
            {
                $this->dependency = $dependency;
            }

            public function __invoke(): string
            {
                return $this->dependency->process();
            }
        };

        $result = $action();

        expect($result)->toBe('processed');
    });

    it('can return null', function () {
        $action = new class
        {
            public function __invoke(): null
            {
                return null;
            }
        };

        $result = $action();

        expect($result)->toBeNull();
    });

    it('can handle exceptions', function () {
        $action = new class
        {
            public function __invoke(): void
            {
                throw new \Exception('Something went wrong');
            }
        };

        expect(fn () => $action())
            ->toThrow(\Exception::class, 'Something went wrong');
    });

    it('can be called with call_user_func', function () {
        $action = new class
        {
            public function __invoke($param): string
            {
                return "Called with: {$param}";
            }
        };

        $result = call_user_func($action, 'test');

        expect($result)->toBe('Called with: test');
    });

    it('can be called with call_user_func_array', function () {
        $action = new class
        {
            public function __invoke($param1, $param2): string
            {
                return "Called with: {$param1}, {$param2}";
            }
        };

        $result = call_user_func_array($action, ['hello', 'world']);

        expect($result)->toBe('Called with: hello, world');
    });

    it('works with array_map', function () {
        $action = new class
        {
            public function __invoke($item): string
            {
                return strtoupper($item);
            }
        };

        $result = array_map($action, ['hello', 'world']);

        expect($result)->toBe(['HELLO', 'WORLD']);
    });

    it('can be used as a callback', function () {
        $action = new class
        {
            public function __invoke($item): bool
            {
                return strlen($item) > 3;
            }
        };

        $items = ['a', 'hello', 'hi', 'world'];
        $result = array_filter($items, $action);

        expect(array_values($result))->toBe(['hello', 'world']);
    });

    it('maintains state between invocations', function () {
        $action = new class
        {
            private int $counter = 0;

            public function __invoke(): int
            {
                return ++$this->counter;
            }
        };

        expect($action())->toBe(1)
            ->and($action())->toBe(2)
            ->and($action())->toBe(3);
    });

    it('can handle complex return types', function () {
        $action = new class
        {
            public function __invoke(): object
            {
                return (object) [
                    'message' => 'success',
                    'data' => ['item1', 'item2'],
                    'timestamp' => time(),
                ];
            }
        };

        $result = $action();

        expect($result)->toBeObject()
            ->and($result->message)->toBe('success')
            ->and($result->data)->toBe(['item1', 'item2'])
            ->and($result->timestamp)->toBeInt();
    });

    it('can access instance properties', function () {
        $action = new class
        {
            private string $value = 'instance property';

            public function __invoke(): string
            {
                return $this->value;
            }
        };

        $result = $action();

        expect($result)->toBe('instance property');
    });
});
