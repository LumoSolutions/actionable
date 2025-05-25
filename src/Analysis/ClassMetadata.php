<?php

namespace LumoSolutions\Actionable\Analysis;

class ClassMetadata
{
    /** @var FieldMetadata[] */
    public readonly array $constructorFields;

    /** @var FieldMetadata[] */
    public readonly array $properties;

    /**
     * @param FieldMetadata[] $constructorFields
     * @param FieldMetadata[] $properties
     */
    public function __construct(array $constructorFields, array $properties)
    {
        $this->constructorFields = $constructorFields;
        $this->properties = $properties;
    }

    public function getConstructorField(string $propertyName): ?FieldMetadata
    {
        foreach ($this->constructorFields as $field) {
            if ($field->propertyName === $propertyName) {
                return $field;
            }
        }

        return null;
    }

    public function getProperty(string $propertyName): ?FieldMetadata
    {
        foreach ($this->properties as $property) {
            if ($property->propertyName === $propertyName) {
                return $property;
            }
        }

        return null;
    }

    public function getVisibleProperties(): array
    {
        return array_filter($this->properties, fn(FieldMetadata $field) => !$field->shouldIgnore());
    }
}
