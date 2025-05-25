<?php

namespace LumoSolutions\Actionable\Analysis;

readonly class ClassMetadata
{
    /** @var FieldMetadata[] */
    public array $constructorFields;

    /** @var FieldMetadata[] */
    public array $properties;

    /**
     * @param  FieldMetadata[]  $constructorFields
     * @param  FieldMetadata[]  $properties
     */
    public function __construct(array $constructorFields, array $properties)
    {
        $this->constructorFields = $constructorFields;
        $this->properties = $properties;
    }

    public function getVisibleProperties(): array
    {
        return array_filter($this->properties, fn (FieldMetadata $field) => ! $field->shouldIgnore());
    }
}
