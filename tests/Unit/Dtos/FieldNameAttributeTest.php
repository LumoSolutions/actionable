<?php

use LumoSolutions\Actionable\Tests\Setup\Dtos\FieldNameDto;

describe('DTOs', function () {
    describe('FieldNameAttribute', function () {
        it('can be constructed with a name', function () {
            $dto = new FieldNameDto(name: 'company', description: 'description');

            expect($dto->name)->toBe('company');
        });

        it('can be output to an array with correct keys', function () {
            $dto = new FieldNameDto(name: 'company', description: 'description');

            expect($dto->toArray())->toHaveKey('company_name')
                ->and($dto->toArray()['company_name'])->toEqual('company')
                ->and($dto->toArray())->not()->toHaveKey('name');
        });

        it('can be built from an array', function () {
            $dto = FieldNameDto::fromArray(['company_name' => 'company']);
            expect($dto->name)->toBe('company')
                ->and($dto->toArray())->toHaveKey('company_name')
                ->and($dto->toArray())->not()->toHaveKey('name');
        });

        it('gets a default value where not set on construction', function () {
            $dto = new FieldNameDto(name: 'company', description: 'description');

            expect($dto->default)->toBe('default_value');
        });

        it('gets a default value where not set fromArray', function () {
            $dto = FieldNameDto::fromArray(['company_name' => 'company']);

            expect($dto->default)->toBe('default_value');
        });

        it('sets a null value for a nullable field, where not provided', function () {
            $dto = FieldNameDto::fromArray(['company_name' => 'company']);

            expect($dto->description)->toBeNull();
        });
    });
});
