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
                ->expectsOutputToContain('Scanning actions in namespace: App\\Actions\\')
                ->expectsOutputToContain('App\\Actions\\Test1');
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
                    'use App\\Actions\\DifferentDir\\Test8;',
                    'use App\\Actions\\AnotherFolder\\Test9 as DoesWork;',
                ]),
                $actionPath
            );
            File::replaceInFile(
                'public function handle(): void',
                'public function handle(Test8 $action, DoesWork $test): DoesWork',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static DoesWork run(Test8 $action, DoesWork $test)')
                ->expectsOutputToContain('@method static void dispatch(Test8 $action, DoesWork $test)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, Test8 $action, DoesWork $test)');

            $this->artisan(ActionsIdeHelperCommand::class, ['--namespace' => 'App\\Actions'])
                ->assertExitCode(0);

            $fileContents = file_get_contents($actionPath);
            expect($fileContents)->toContain('@method static DoesWork run(Test8 $action, DoesWork $test)')
                ->and($fileContents)->toContain('@method static void dispatch(Test8 $action, DoesWork $test)')
                ->and($fileContents)->toContain('@method static void dispatchOn(string $queue, Test8 $action, DoesWork $test)');
        });

        it('correctly handles default values', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test10', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test10.php');

            File::replaceInFile(
                'public function handle(): void',
                "public function handle(string \$default = 'test', ?string \$can_null = null, array \$arr = []): void",
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
                ->assertExitCode(0);
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

        it('handles notification of removed docblocks', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test14', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test14.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(string $type): void',
                $actionPath
            );

            File::replaceInFile(
                'class Test14',
                implode(PHP_EOL, [
                    '/**',
                    ' * @method static void run(string $type_wrong)',
                    ' * @method static void dispatch(string $type_wrong)',
                    ' * @method static void dispatchOn(string $queue, string $type_wrong)',
                    ' */',
                    'class Test14',
                ]),
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true]
            )
                ->assertExitCode(0)
                ->expectsOutputToContain('- @method static void run(string $type_wrong)')
                ->expectsOutputToContain('- @method static void dispatch(string $type_wrong)')
                ->expectsOutputToContain('- @method static void dispatchOn(string $queue, string $type_wrong)')
                ->expectsOutputToContain('+ @method static void run(string $type)')
                ->expectsOutputToContain('+ @method static void dispatch(string $type)')
                ->expectsOutputToContain('+ @method static void dispatchOn(string $queue, string $type)');
        });

        it('handles actions with no handle method', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test15', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test15.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function somethingElse(string $type): void',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('No actions found or no changes needed.');
        });

        it('skips non php files', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test16', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test16.php');

            File::move($actionPath, str_replace('.php', '.txt', $actionPath));

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('No actions found or no changes needed.');
        });

        it('handles text comments on a single line', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test17', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test17.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function something(string $type): void',
                $actionPath
            );

            File::replaceInFile(
                'class Test17',
                implode(PHP_EOL, [
                    '/** This is a test action',
                    ' * @method static void run(string $type)',
                    ' * @method static void dispatch(string $type)',
                    ' * @method static void dispatchOn(string $queue, string $type)',
                    ' */',
                    'class Test17',
                ]),
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class)
                ->assertExitCode(0);

            $content = file_get_contents($actionPath);
            expect($content)->toContain('/** This is a test action */')
                ->and($content)->not->toContain('@method static void run(string $type)')
                ->and($content)->not->toContain('@method static void dispatch(string $type)')
                ->and($content)->not->toContain('@method static void dispatchOn(string $queue, string $type)');
        });

        it('handles nullable return type', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test18', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test18.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(?string $type): ?string',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static string|null run(?string $type)')
                ->expectsOutputToContain('@method static void dispatch(?string $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, ?string $type)');
        });

        it('handles multiple return types', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test19', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test19.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(?string $type): string|bool',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static string|bool run(?string $type)')
                ->expectsOutputToContain('@method static void dispatch(?string $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, ?string $type)');
        });

        it('handles multiple nullable return types', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test20', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test20.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(string|int $type): string|int|null',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static string|int|null run(string|int $type)')
                ->expectsOutputToContain('@method static void dispatch(string|int $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, string|int $type)');
        });

        it('handles full namespaces', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test21', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test22', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test21.php');

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(\\App\\Actions\\Diff\\Test22 $type): void',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static void run(\\App\\Actions\\Diff\\Test22 $type)')
                ->expectsOutputToContain('@method static void dispatch(\\App\\Actions\\Diff\\Test22 $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, \\App\\Actions\\Diff\\Test22 $type)');
        });

        it('handles full namespaces when included', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test23', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test24', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test23.php');

            File::replaceInFile(
                'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                implode(PHP_EOL, [
                    'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                    'use App\\Actions\\Diff\\Test24;',
                ]),
                $actionPath
            );

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(\\App\\Actions\\Diff\\Test24 $type): \\App\\Actions\\Diff\\Test24',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static Test24 run(Test24 $type)')
                ->expectsOutputToContain('@method static void dispatch(Test24 $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, Test24 $type)');
        });

        it('handles grouped using statements', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test25', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test26', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test27', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test28', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test25.php');

            File::replaceInFile(
                'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                implode(PHP_EOL, [
                    'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                    'use App\\Actions\\Diff\\{Test26, Test27, Test28};',
                ]),
                $actionPath
            );

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(\\App\\Actions\\Diff\\Test26 $type): \\App\\Actions\\Diff\\Test27',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static Test27 run(Test26 $type)')
                ->expectsOutputToContain('@method static void dispatch(Test26 $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, Test26 $type)');
        });

        it('handles single line use statements', function () {
            Artisan::call(MakeActionCommand::class, ['name' => 'Test29', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test30', '--dispatchable' => true]);
            Artisan::call(MakeActionCommand::class, ['name' => 'Diff\\Test31', '--dispatchable' => true]);
            $actionPath = join_paths(app_path(), 'Actions', 'Test29.php');

            File::replaceInFile(
                'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                implode(PHP_EOL, [
                    'use LumoSolutions\\Actionable\\Traits\\IsDispatchable;',
                    'use App\\Actions\\Diff\\Test30, App\\Actions\\Diff\\Test31;',
                ]),
                $actionPath
            );

            File::replaceInFile(
                'public function handle(): void',
                'public function handle(\\App\\Actions\\Diff\\Test30 $type): \\App\\Actions\\Diff\\Test31',
                $actionPath
            );

            $this->artisan(ActionsIdeHelperCommand::class, [
                '--namespace' => 'App\\Actions',
                '--dry-run' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutputToContain('@method static Test31 run(Test30 $type)')
                ->expectsOutputToContain('@method static void dispatch(Test30 $type)')
                ->expectsOutputToContain('@method static void dispatchOn(string $queue, Test30 $type)');
        });
    });
});
