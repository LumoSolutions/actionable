<?php

namespace LumoSolutions\Actionable\Tests\Setup\Dtos;

use LumoSolutions\Actionable\Attributes\ArrayOf;
use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class ArrayOfDto
{
    use ArrayConvertible;

    public function __construct(
        #[ArrayOf(ItemDto::class)]
        public array $items,
    ) {}
}
