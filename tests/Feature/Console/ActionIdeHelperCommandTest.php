<?php

use LumoSolutions\Actionable\Console\Commands\ActionsIdeHelperCommand;
use LumoSolutions\Actionable\Console\Commands\MakeActionCommand;

use function Illuminate\Filesystem\join_paths;

describe('Console', function () {
    describe('ActionsIdeHelperCommand', function () {
        it('identifies action', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test1']);

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('Scanning for Action classes in namespace: App\\Actions')
                ->expectsOutputToContain(join_paths('app', 'Actions', 'Test1.php'));
        });

        it('identifies and documents run in action', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test2']);
            $actionPath = join_paths(app_path(), 'Actions', 'Test2.php');

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static void run()')
                ->doesntExpectOutputToContain('@method static void dispatch')
                ->doesntExpectOutputToContain('@method static void dispatchOn');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static void run()')
                ->and($fileContents)->not->toContain('@method static void dispatch')
                ->and($fileContents)->not->toContain('@method static void dispatchOn');
        });

        it('identifies and documents dispatch in action', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test3', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test3.php');

            File::replaceInFile('use IsRunnable, IsDispatchable;', 'use IsDispatchable;', $actionPath);

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->doesntExpectOutputToContain('@method static void run()')
                ->expectsOutputToContain('@method static void dispatch()')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue)');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->not->toContain('@method static void run()')
                ->and($fileContents)->toContain('@method static void dispatch()')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue)');
        });

        it('identifies and documents both run and dispatch together', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test4', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test4.php');

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static void run()')
                ->expectsOutputToContain('@method static void dispatch()')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue)');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static void run()')
                ->and($fileContents)->toContain('@method static void dispatch()')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue)');
        });

        it('identifies and documents correct single parameters', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test5', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test5.php');

            File::replaceInFile('public function handle(): void', 'public function handle(string $type): array', $actionPath);

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static array run(string $type)')
                ->expectsOutputToContain('@method static void dispatch(string $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, string $type)');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static array run(string $type)')
                ->and($fileContents)->toContain('@method static void dispatch(string $type)')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue, string $type)');
        });

        it('identifies and documents correct multiple parameters', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test6', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test6.php');

            File::replaceInFile('public function handle(): void', 'public function handle(string $type, Test6 $action): array', $actionPath);

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static array run(string $type, \\App\\Actions\\Test6 $action)')
                ->expectsOutputToContain('@method static void dispatch(string $type, \\App\\Actions\\Test6 $action)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, string $type, \\App\\Actions\\Test6 $action)');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static array run(string $type, \\App\\Actions\\Test6 $action)')
                ->and($fileContents)->toContain('@method static void dispatch(string $type, \\App\\Actions\\Test6 $action)')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue, string $type, \\App\\Actions\\Test6 $action)');
        });

        it('correctly uses usings for short-class usage', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test7', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => sprintf('DifferentDir%sTest8', DIRECTORY_SEPARATOR), '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => sprintf('AnotherFolder%sTest9', DIRECTORY_SEPARATOR), '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test7.php');

            File::replaceInFile(
                'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                implode(PHP_EOL, [
                    'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                    'use App\\Actions\\Testing\\Test8;',
                    'use App\\Actions\\AnotherFolder\\Test9 as DoesWork;',
                ]),
                $actionPath
            );
            File::replaceInFile(
                'public function handle(): void',
                'public function handle(Test8 $action, DoesWork $test): void',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static void run(Test8 $action, DoesWork $test)')
                ->expectsOutputToContain('@method static void dispatch(Test8 $action, DoesWork $test)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, Test8 $action, DoesWork $test)');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static void run(Test8 $action, DoesWork $test)')
                ->and($fileContents)->toContain('@method static void dispatch(Test8 $action, DoesWork $test)')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue, Test8 $action, DoesWork $test)');
        });

        it('correctly handles default values', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test10', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test10.php');

            File::replaceInFile(
                'public function handle(): void',
                "public function handle(string \$default = 'test', string \$can_null = null, array \$arr = []): void",
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static void run(string $default = \'test\', ?string $can_null = null, array $arr = [])')
                ->expectsOutputToContain('@method static void dispatch(string $default = \'test\', ?string $can_null = null, array $arr = [])')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, string $default = \'test\', ?string $can_null = null, array $arr = [])');
        });

        it('correctly updates existing docblocks', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test11', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test11.php');

            File::replaceInFile(
                'class Test11',
                implode(PHP_EOL, [
                    '/**',
                    ' * @method static void leave_this_one(string $valid)',
                    ' * @method static void run(string $invalid)',
                    ' * @method static void dispatch(string $invalid)',
                    ' * @method static void dispatchOn(string $queue, string $invalid)',
                    ' */',
                    'class Test11',
                ]),
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static void leave_this_one(string $valid)')
                ->and($fileContents)->not->toContain('@method static void run(string $invalid)')
                ->and($fileContents)->toContain('@method static void run()')
                ->and($fileContents)->not->toContain('@method static void dispatch(string $invalid)')
                ->and($fileContents)->toContain('@method static void dispatch()')
                ->and($fileContents)->not->toContain('@method static void dispatchOn(string $queue, string $invalid)')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue)');
        });

        it('correctly ignores non-action files', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test12']);
            $actionPath = join_paths(app_path(), 'Actions', 'Test12.php');

            File::replaceInFile('use IsRunnable;', '', $actionPath);

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->not->toContain('@method static void run')
                ->and($fileContents)->not->toContain('@method static void dispatch')
                ->and($fileContents)->not->toContain('@method static void dispatchOn');
        });

        it('handles no files', function () {
            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions\\DoesNotExist'])
                ->assertExitCode(1);
        });

        it('handles action without handle method', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test13']);
            $actionPath = join_paths(app_path(), 'Actions', 'Test13.php');

            File::replaceInFile('public function handle(): void', 'public function something_else(): void', $actionPath);

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->not->toContain('@method static void run');
        });

    });
});
