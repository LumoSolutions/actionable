<?php

namespace LumoSolutions\Actionable\Analysis;

class FieldMetadata
{
    public function __construct(
        public readonly string $propertyName,
        public readonly string $fieldName,
        public readonly ?string $type = null,
        public readonly bool $ignore = false,
        public readonly ?string $dateFormat = null,
        public readonly ?string $arrayOf = null,
        public readonly bool $hasDefault = false,
        public readonly mixed $defaultValue = null,
        public readonly bool $allowsNull = false
    ) {}

    public function isDateTime(): bool
    {
        return $this->dateFormat !== null;
    }

    public function isNested(): bool
    {
        return $this->type !== null
            && class_exists($this->type)
            && method_exists($this->type, 'fromArray');
    }

    public function getNestedClass(): ?string
    {
        return $this->isNested() ? $this->type : null;
    }

    public function isArrayOf(): bool
    {
        return $this->arrayOf !== null;
    }

    public function shouldIgnore(): bool
    {
        return $this->ignore;
    }
}
