<?php

use LumoSolutions\Actionable\Tests\Setup\Dtos\IgnoreDto;

describe('DTOs', function () {
    describe('IgnoreAttribute', function () {
        it('can be constructed with a secret', function () {
            $dto = new IgnoreDto(name: 'company', secret: 'api_key');

            expect($dto->name)->toBe('company')
                ->and($dto->secret)->toBe('api_key');
        });

        it('can be output as an array', function () {
            $dto = new IgnoreDto(name: 'company', secret: 'api_key');

            expect($dto->toArray())->toHaveKey('name')
                ->and($dto->toArray())->not()->toHaveKey('secret');
        });

        it('can be built from an array', function () {
            $dto = IgnoreDto::fromArray([
                'name' => 'company', 'secret' => 'api_key',
            ]);

            expect($dto->name)->toBe('company')
                ->and($dto->secret)->toBe('api_key');
        });

        it('can collect and not see secret', function () {
            $dto = IgnoreDto::fromArray([
                'name' => 'company', 'secret' => 'api_key',
            ]);

            $collection = $dto->collect();

            expect($collection->has('secret'))->toBeFalse();
        });

    });
});
