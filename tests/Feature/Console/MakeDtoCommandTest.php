<?php

use Illuminate\Support\Facades\File;
use LumoSolutions\Actionable\Console\Commands\MakeDtoCommand;

describe('Console', function () {
    describe('MakeDtoCommand', function () {
        it('creates a dto file successfully', function () {
            $this->artisan(MakeDtoCommand::class, ['name' => 'ExampleDto'])
                ->assertExitCode(0)
                ->expectsOutput('DTO App\Dtos\ExampleDto created successfully.');

            $expectedPath = app_path('Dtos/ExampleDto.php');
            expect(File::exists($expectedPath))->toBeTrue();

            $content = File::get($expectedPath);
            expect($content)->toContain('namespace App\Dtos;')
                ->and($content)->toContain('class ExampleDto')
                ->and($content)->toContain('use LumoSolutions\Actionable\Traits\ArrayConvertible;')
                ->and($content)->toContain('public function __construct(')
                ->and($content)->toContain('// public string $property,');
        });
    });
});
