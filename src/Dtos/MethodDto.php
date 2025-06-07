<?php

namespace LumoSolutions\Actionable\Dtos;

use LumoSolutions\Actionable\Attributes\ArrayOf;
use LumoSolutions\Actionable\Dtos\Class\RelationDto;

readonly class MethodDto
{
    public function __construct(
        public string $name,
        public string $rawReturnType,

        #[ArrayOf(RelationDto::class)]
        public array $returnTypes = [],

        public string $visibility = 'public',
        public bool $isStatic = false,
        public bool $isAbstract = false,
        public bool $isFinal = false,

        #[ArrayOf(ParameterDto::class)]
        public array $parameters = [],
    ) {}
}
