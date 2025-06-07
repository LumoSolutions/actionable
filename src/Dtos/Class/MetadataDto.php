<?php

namespace LumoSolutions\Actionable\Dtos\Class;

use LumoSolutions\Actionable\Attributes\ArrayOf;
use LumoSolutions\Actionable\Dtos\MethodDto;

readonly class MetadataDto
{
    public function __construct(
        public string $className,
        public ?string $namespace,
        public string $filePath,
        public ?string $docBlock = null,
        public ?RelationDto $extends = null,

        #[ArrayOf(RelationDto::class)]
        public array $includes = [],

        #[ArrayOf(RelationDto::class)]
        public array $traits = [],

        #[ArrayOf(MethodDto::class)]
        public array $methods = [],
    ) {}
}
