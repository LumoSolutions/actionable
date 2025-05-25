<?php

use LumoSolutions\Actionable\Tests\Setup\Dtos\ArrayOfDto;
use LumoSolutions\Actionable\Tests\Setup\Dtos\ItemDto;

describe('DTOs', function () {
    describe('ArrayOfAttribute', function () {
        it('can be constructed with items', function () {
            $dto = new ArrayOfDto(
                [new ItemDto('1'), new ItemDto('2')]
            );

            expect($dto->items)->toHaveCount(2)
                ->and($dto->items)->toBeArray(ItemDto::class)
                ->and($dto->items[0])->toBeInstanceOf(ItemDto::class);
        });

        it('can be output as an array', function () {
            $dto = new ArrayOfDto(
                [new ItemDto('1'), new ItemDto('2')]
            );

            expect($dto->toArray())->toHaveKey('items')
                ->and($dto->toArray()['items'])->toHaveCount(2)
                ->and($dto->toArray()['items'][0])->toHaveKey('name')
                ->and($dto->toArray()['items'][0]['name'])->toBe('1');
        });

        it('can be built from an array', function () {
            $array = [
                'items' => [
                    ['name' => '1'],
                    ['name' => '2'],
                ],
            ];

            $dto = ArrayOfDto::fromArray($array);

            expect($dto->items)->toHaveCount(2)
                ->and($dto->items)->toBeArray(ItemDto::class)
                ->and($dto->items[0])->toBeInstanceOf(ItemDto::class);
        });
    });
});
