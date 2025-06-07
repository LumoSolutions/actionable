<?php

namespace LumoSolutions\Actionable\Dtos;

use LumoSolutions\Actionable\Attributes\ArrayOf;
use LumoSolutions\Actionable\Dtos\Class\RelationDto;

readonly class ParameterDto
{
    public function __construct(
        public string $name,
        public string $rawType,

        #[ArrayOf(RelationDto::class)]
        public array $types = [],

        public bool $isOptional = false,
        public bool $isVariadic = false,
        public bool $isReference = false,
        public bool $hasDefaultValue = false,
        public mixed $defaultValue = null,
        public int $position = 0,
    ) {}
}
