<?php

namespace LumoSolutions\Actionable\Analysis;

use LumoSolutions\Actionable\Attributes\{FieldName, Ignore, DateFormat, ArrayOf};
use ReflectionClass;
use ReflectionProperty;
use ReflectionParameter;

class FieldAnalyzer
{
    private static array $cache = [];

    public static function analyzeClass(string $className): ClassMetadata
    {
        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        $constructorFields = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $constructorFields[] = self::analyzeConstructorParameter($param);
            }
        }

        $properties = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[] = self::analyzeProperty($property);
        }

        $metadata = new ClassMetadata($constructorFields, $properties);
        self::$cache[$className] = $metadata;

        return $metadata;
    }

    private static function analyzeConstructorParameter(ReflectionParameter $param): FieldMetadata
    {
        $fieldName = self::getFieldName($param);
        $type = self::getType($param);
        $ignore = self::hasAttribute($param, Ignore::class);
        $dateFormat = self::getDateFormat($param);
        $arrayOf = self::getArrayOf($param);

        return new FieldMetadata(
            propertyName: $param->getName(),
            fieldName: $fieldName,
            type: $type,
            ignore: $ignore,
            dateFormat: $dateFormat,
            arrayOf: $arrayOf,
            hasDefault: $param->isDefaultValueAvailable(),
            defaultValue: $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            allowsNull: $param->allowsNull()
        );
    }

    private static function analyzeProperty(ReflectionProperty $property): FieldMetadata
    {
        $fieldName = self::getFieldName($property);
        $type = self::getType($property);
        $ignore = self::hasAttribute($property, Ignore::class);
        $dateFormat = self::getDateFormat($property);
        $arrayOf = self::getArrayOf($property);

        return new FieldMetadata(
            propertyName: $property->getName(),
            fieldName: $fieldName,
            type: $type,
            ignore: $ignore,
            dateFormat: $dateFormat,
            arrayOf: $arrayOf
        );
    }

    private static function getFieldName(ReflectionParameter|ReflectionProperty $target): string
    {
        $attributes = $target->getAttributes(FieldName::class);
        if (!empty($attributes)) {
            $fieldNameAttribute = $attributes[0]->newInstance();
            return $fieldNameAttribute->name;
        }

        return $target->getName();
    }

    private static function hasAttribute(ReflectionParameter|ReflectionProperty $target, string $attributeClass): bool
    {
        return !empty($target->getAttributes($attributeClass));
    }

    private static function getDateFormat(ReflectionParameter|ReflectionProperty $target): ?string
    {
        $attributes = $target->getAttributes(DateFormat::class);
        if (!empty($attributes)) {
            $dateFormatAttribute = $attributes[0]->newInstance();
            return $dateFormatAttribute->format;
        }

        return null;
    }

    private static function getArrayOf(ReflectionParameter|ReflectionProperty $target): ?string
    {
        $attributes = $target->getAttributes(ArrayOf::class);
        if (!empty($attributes)) {
            $arrayOfAttribute = $attributes[0]->newInstance();
            return $arrayOfAttribute->class;
        }

        return null;
    }

    private static function getType(ReflectionParameter|ReflectionProperty $target): ?string
    {
        $type = $target->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        return null;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
