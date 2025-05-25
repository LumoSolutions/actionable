<?php

namespace LumoSolutions\Actionable\Tests\Setup\Dtos;

use LumoSolutions\Actionable\Attributes\FieldName;
use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class FieldNameDto
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('company_name')]
        public string $name,
        public ?string $description,
        public string $default = 'default_value',
    ) {}
}
