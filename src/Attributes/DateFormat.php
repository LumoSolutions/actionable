<?php

namespace LumoSolutions\Actionable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DateFormat
{
    public function __construct(
        public string $format = 'Y-m-d H:i:s'
    ) {}
}
