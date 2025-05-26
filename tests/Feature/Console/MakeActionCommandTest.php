<?php

use Illuminate\Support\Facades\File;
use LumoSolutions\Actionable\Console\Commands\MakeActionCommand;

describe('Console', function () {
    describe('MakeActionCommand', function () {
        it('uses application stub when it exists', function () {
            $this->copyStubs();
            $this->artisan(MakeActionCommand::class, ['name' => 'CustomAction'])
                ->assertExitCode(0);

            $path = app_path('/Actions/CustomAction.php');
            $content = File::get($path);

            // Should contain custom content from application stub
            expect($content)->toContain('// This is a custom application stub');
        });

        it('falls back to package stub when application stub does not exist', function () {
            // Test with a stub that only exists in package fixtures
            $this->artisan(MakeActionCommand::class, ['name' => 'StandardAction'])
                ->assertExitCode(0);

            $path = app_path('Actions/StandardAction.php');
            $content = File::get($path);

            // Should contain standard package stub content
            expect($content)->toContain('use LumoSolutions\Actionable\Traits\IsRunnable;')
                ->and($content)->not->toContain('custom application stub');
        });

        it('creates a basic action file successfully', function () {
            $this->artisan(MakeActionCommand::class, ['name' => 'TestAction'])
                ->assertExitCode(0)
                ->expectsOutput('Action App\Actions\TestAction created successfully.');

            $expectedPath = app_path('Actions/TestAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions;')
                ->and($content)->toContain('class TestAction')
                ->and($content)->toContain('use LumoSolutions\Actionable\Traits\IsRunnable;')
                ->and($content)->toContain('public function handle(): void');
        });

        it('creates an invokable action file', function () {
            $this->artisan(MakeActionCommand::class, [
                'name' => 'InvokableAction',
                '--invokable' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutput('Action App\Actions\InvokableAction created successfully.');

            $expectedPath = app_path('Actions/InvokableAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions;')
                ->and($content)->toContain('class InvokableAction')
                ->and($content)->toContain('public function __invoke(): mixed')
                ->and($content)->not->toContain('IsRunnable');
        });

        it('creates a dispatchable action file', function () {
            $this->artisan(MakeActionCommand::class, [
                'name' => 'DispatchableAction',
                '--dispatchable' => true,
            ])
                ->assertExitCode(0)
                ->expectsOutput('Action App\Actions\DispatchableAction created successfully.');

            $expectedPath = app_path('Actions/DispatchableAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions;')
                ->and($content)->toContain('class DispatchableAction')
                ->and($content)->toContain('use LumoSolutions\Actionable\Traits\IsRunnable;')
                ->and($content)->toContain('use LumoSolutions\Actionable\Traits\IsDispatchable;')
                ->and($content)->toContain('public function handle(): void');
        });

        it('creates nested namespace actions', function () {
            $this->artisan(MakeActionCommand::class, ['name' => 'User/CreateUserAction'])
                ->assertExitCode(0);

            $expectedPath = app_path('Actions/User/CreateUserAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions\User;')
                ->and($content)->toContain('class CreateUserAction');
        });

        it('creates directories when they do not exist', function () {
            $nestedPath = app_path('/Actions/Deep/Nested');
            expect(File::isDirectory($nestedPath))->toBeFalse();

            $this->artisan(MakeActionCommand::class, ['name' => 'Deep\\Nested\\DeepAction'])
                ->assertExitCode(0)
                ->expectsOutput('Action App\Actions\Deep\Nested\DeepAction created successfully.');

            expect(File::isDirectory($nestedPath))->toBeTrue();

            $expectedPath = $nestedPath.'/DeepAction.php';
            expect(File::exists($expectedPath))->toBeTrue();
        });

        it('selects correct stub based on invokable option', function () {
            $this->artisan(MakeActionCommand::class, [
                'name' => 'InvokableTest',
                '--invokable' => true,
            ])->assertExitCode(0);

            $path = app_path('Actions/InvokableTest.php');
            $content = File::get($path);

            expect($content)->toContain('public function __invoke(): mixed')
                ->and($content)->not->toContain('public function handle(): void');
        });

        it('selects correct stub based on dispatchable option', function () {
            $this->artisan(MakeActionCommand::class, [
                'name' => 'DispatchableTest',
                '--dispatchable' => true,
            ])->assertExitCode(0);

            $path = app_path('Actions/DispatchableTest.php');
            $content = File::get($path);

            expect($content)->toContain('use LumoSolutions\Actionable\Traits\IsDispatchable;')
                ->and($content)->toContain('public function handle(): void');
        });

        it('prioritizes dispatchable over invokable when both flags are set', function () {
            $this->artisan(MakeActionCommand::class, [
                'name' => 'PriorityTest',
                '--invokable' => true,
                '--dispatchable' => true,
            ])->assertExitCode(0);

            $path = app_path('Actions/PriorityTest.php');
            $content = File::get($path);

            // Should use dispatchable stub, not invokable
            expect($content)->toContain('use LumoSolutions\Actionable\Traits\IsDispatchable;')
                ->and($content)->toContain('public function handle(): void')
                ->and($content)->not->toContain('public function __invoke(): mixed');
        });

        it('handles class names with leading slashes', function () {
            $this->artisan(MakeActionCommand::class, ['name' => '/LeadingSlashAction'])
                ->assertExitCode(0);

            $expectedPath = app_path('Actions/LeadingSlashAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions;')
                ->and($content)->toContain('class LeadingSlashAction');
        });

        it('converts forward slashes to backslashes in namespaces', function () {
            $this->artisan(MakeActionCommand::class, ['name' => 'Admin/User/AdminUserAction'])
                ->assertExitCode(0);

            $expectedPath = app_path('Actions/Admin/User/AdminUserAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions\Admin\User;')
                ->and($content)->toContain('class AdminUserAction');
        });

        it('handles already qualified class names', function () {
            $this->artisan(MakeActionCommand::class, ['name' => 'App\Actions\QualifiedAction'])
                ->assertExitCode(0);

            $expectedPath = app_path('Actions/QualifiedAction.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Actions;')
                ->and($content)->toContain('class QualifiedAction');
        });

        it('throws an error where the action already exists', function () {
            $this->artisan(MakeActionCommand::class, ['name' => 'ExistingAction'])
                ->assertExitCode(0);

            // Try to create the same action again
            $this->artisan(MakeActionCommand::class, ['name' => 'ExistingAction'])
                ->assertExitCode(1)
                ->expectsOutput('Action App\Actions\ExistingAction already exists!');
        });
    });
});
