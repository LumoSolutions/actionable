<?php

namespace LumoSolutions\Actionable\Conversion;

use LumoSolutions\Actionable\Analysis\{FieldAnalyzer, ClassMetadata, FieldMetadata};
use DateTime;

class DataConverter
{
    public static function fromArray(string $className, array $data): object
    {
        $metadata = FieldAnalyzer::analyzeClass($className);
        $args = [];

        foreach ($metadata->constructorFields as $field) {
            $value = self::getValueFromArray($data, $field);
            $transformedValue = self::transformFromArray($field, $value);
            $args[$field->propertyName] = $transformedValue;
        }

        return new $className(...$args);
    }

    public static function toArray(object $object): array
    {
        $metadata = FieldAnalyzer::analyzeClass($object::class);
        $result = [];

        foreach ($metadata->getVisibleProperties() as $field) {
            $value = $object->{$field->propertyName};
            $transformedValue = self::transformToArray($field, $value);
            $result[$field->fieldName] = $transformedValue;
        }

        return $result;
    }

    private static function getValueFromArray(array $data, FieldMetadata $field): mixed
    {
        if (isset($data[$field->fieldName])) {
            return $data[$field->fieldName];
        }

        if ($field->allowsNull) {
            return null;
        }

        if ($field->hasDefault) {
            return $field->defaultValue;
        }

        return null;
    }

    private static function transformFromArray(FieldMetadata $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // Handle DateTime conversion
        if ($field->isDateTime() && is_string($value)) {
            $dateTime = DateTime::createFromFormat($field->dateFormat, $value);
            return $dateTime ?: $value;
        }

        // Handle nested objects
        if ($field->isNested() && is_array($value)) {
            $nestedClass = $field->getNestedClass();
            if (method_exists($nestedClass, 'fromArray')) {
                return $nestedClass::fromArray($value);
            }
        }

        // Handle arrays of objects
        if ($field->isArrayOf() && is_array($value)) {
            $arrayOfClass = $field->arrayOf;
            if (method_exists($arrayOfClass, 'fromArray')) {
                return array_map(
                    fn($item) => is_array($item) ? $arrayOfClass::fromArray($item) : $item,
                    $value
                );
            }
        }

        return $value;
    }

    private static function transformToArray(FieldMetadata $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // Handle DateTime conversion
        if ($field->isDateTime() && $value instanceof DateTime) {
            return $value->format($field->dateFormat);
        }

        // Handle nested objects
        if ($field->isNested() && is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        // Handle arrays of objects
        if ($field->isArrayOf() && is_array($value)) {
            return array_map(function($item) {
                return is_object($item) && method_exists($item, 'toArray')
                    ? $item->toArray()
                    : $item;
            }, $value);
        }

        return $value;
    }
}
