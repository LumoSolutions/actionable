<?php

namespace LumoSolutions\Actionable\Traits;

use Illuminate\Support\Collection;
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

    public function collect(): Collection
    {
        return collect($this->toArray());
    }
}
