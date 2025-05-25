<?php

use LumoSolutions\Actionable\Tests\Setup\Dtos\DateFormatDto;

describe('DTOs', function () {
    describe('DateFormatAttribute', function () {
        it('can be constructed with different date formats', function () {
            $dto = new DateFormatDto(
                eventDate: new DateTime('2024-01-15'),
                createdAt: new DateTime('2024-01-15 14:30:00'),
                updatedAt: new DateTime('2024-01-15 14:30:00')
            );

            expect($dto->eventDate)->toBeInstanceOf(DateTime::class)
                ->and($dto->createdAt)->toBeInstanceOf(DateTime::class)
                ->and($dto->updatedAt)->toBeInstanceOf(DateTime::class);
        });

        it('can be output to array with formatted dates', function () {
            $dto = new DateFormatDto(
                eventDate: new DateTime('2024-01-15'),
                createdAt: new DateTime('2024-01-15 14:30:00'),
                updatedAt: new DateTime('2024-01-15 14:30:00')
            );

            $array = $dto->toArray();

            expect($array)->toHaveKey('eventDate')
                ->and($array['eventDate'])->toBe('2024-01-15')
                ->and($array)->toHaveKey('createdAt')
                ->and($array['createdAt'])->toBe('15/01/2024 14:30')
                ->and($array)->toHaveKey('updatedAt')
                ->and($array['updatedAt'])->toBe('2024-01-15 14:30:00');
        });

        it('can be built from array with different date formats', function () {
            $dto = DateFormatDto::fromArray([
                'eventDate' => '2024-12-25',
                'createdAt' => '25/12/2024 09:15',
                'updatedAt' => '2024-12-25 09:15:30',
            ]);

            expect($dto->eventDate)->toBeInstanceOf(DateTime::class)
                ->and($dto->eventDate->format('Y-m-d'))->toBe('2024-12-25')
                ->and($dto->createdAt)->toBeInstanceOf(DateTime::class)
                ->and($dto->createdAt->format('d/m/Y H:i'))->toBe('25/12/2024 09:15')
                ->and($dto->updatedAt)->toBeInstanceOf(DateTime::class)
                ->and($dto->updatedAt->format('Y-m-d H:i:s'))->toBe('2024-12-25 09:15:30');
        });

        it('handles default date format when no format specified', function () {
            $dto = new DateFormatDto(
                eventDate: new DateTime('2024-01-01'),
                createdAt: new DateTime('2024-01-01 00:00:00'),
                updatedAt: new DateTime('2024-01-01 00:00:00')
            );

            expect($dto->updatedAt)->toBeInstanceOf(DateTime::class)
                ->and($dto->updatedAt->format('Y-m-d H:i:s'))->toBe('2024-01-01 00:00:00');
        });

        it('can convert between different date formats via array conversion', function () {
            $originalDto = new DateFormatDto(
                eventDate: new DateTime('2024-06-15'),
                createdAt: new DateTime('2024-06-15 12:45:00'),
                updatedAt: new DateTime('2024-06-15 12:45:30')
            );

            $array = $originalDto->toArray();
            $newDto = DateFormatDto::fromArray($array);

            expect($newDto->eventDate)->toBeInstanceOf(DateTime::class)
                ->and($newDto->eventDate->format('Y-m-d'))->toBe($originalDto->eventDate->format('Y-m-d'))
                ->and($newDto->createdAt)->toBeInstanceOf(DateTime::class)
                ->and($newDto->createdAt->format('d/m/Y H:i'))->toBe($originalDto->createdAt->format('d/m/Y H:i'))
                ->and($newDto->updatedAt)->toBeInstanceOf(DateTime::class)
                ->and($newDto->updatedAt->format('Y-m-d H:i:s'))->toBe($originalDto->updatedAt->format('Y-m-d H:i:s'));
        });
    });
});
