<?php

namespace LumoSolutions\Actionable\Dtos\Class;

readonly class RelationDto
{
    public function __construct(
        public string $name,
        public ?string $namespace,
        public ?string $alias = null,
        public bool $isNullable = false,
        public bool $isBuiltIn = false,
    ) {}
}
