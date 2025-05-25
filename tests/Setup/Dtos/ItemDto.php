<?php

namespace LumoSolutions\Actionable\Tests\Setup\Dtos;

use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class ItemDto
{
    use ArrayConvertible;

    public function __construct(
        public string $name,
        public ?ItemDto $parent = null,
    ) {}
}
