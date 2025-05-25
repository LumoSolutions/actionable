<?php

namespace LumoSolutions\Actionable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
readonly class ArrayOf
{
    public function __construct(
        public string $class
    ) {}
}
