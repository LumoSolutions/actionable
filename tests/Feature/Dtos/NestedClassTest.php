<?php

use LumoSolutions\Actionable\Tests\Setup\Dtos\ItemDto;

describe('DTOs', function () {
    describe('NestedClass', function () {
        it('outputs a nested class to array', function () {
            $parent = new ItemDto('parent');
            $child = new ItemDto('child', $parent);

            expect($child->toArray())->toBe([
                'name' => 'child',
                'parent' => [
                    'name' => 'parent',
                    'parent' => null,
                ],
            ]);
        });

        it('converts a nested class from array', function () {
            $data = [
                'name' => 'child',
                'parent' => [
                    'name' => 'parent',
                    'parent' => null,
                ],
            ];

            $child = ItemDto::fromArray($data);

            expect($child->name)->toBe('child')
                ->and($child->parent)->toBeInstanceOf(ItemDto::class)
                ->and($child->parent->name)->toBe('parent')
                ->and($child->parent->parent)->toBeNull();
        });
    });
});
