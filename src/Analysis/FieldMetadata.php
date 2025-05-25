<?php

namespace LumoSolutions\Actionable\Analysis;

readonly class FieldMetadata
{
    public function __construct(
        public string $propertyName,
        public string $fieldName,
        public ?string $type = null,
        public bool $ignore = false,
        public ?string $dateFormat = null,
        public ?string $arrayOf = null,
        public bool $hasDefault = false,
        public mixed $defaultValue = null,
        public bool $allowsNull = false
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
