<?php

namespace LumoSolutions\Actionable\Tests\Setup\Dtos;

use LumoSolutions\Actionable\Attributes\Ignore;
use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class IgnoreDto
{
    use ArrayConvertible;

    public function __construct(
        public string $name,

        #[Ignore]
        public string $secret,
    ) {}
}
