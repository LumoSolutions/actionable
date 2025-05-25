<?php

namespace LumoSolutions\Actionable\Traits;

use LumoSolutions\Actionable\Conversion\DataConverter;

trait ArrayConvertible
{
    public static function fromArray(array $data): static
    {
        return DataConverter::fromArray(static::class, $data);
    }

    public function toArray(): array
    {
        return DataConverter::toArray($this);
    }
}
