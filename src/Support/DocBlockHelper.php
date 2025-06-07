<?php

namespace LumoSolutions\Actionable\Support;

use LumoSolutions\Actionable\Dtos\Class\RelationDto;

class DocBlockHelper
{
    public static function extract($docblock): array
    {
        $lines = explode("\n", $docblock);
        $cleanedLines = [];

        foreach ($lines as $line) {
            // Remove leading/trailing whitespace
            $line = trim($line);

            // Skip opening and closing comment lines
            if ($line === '/**' || $line === '*/') {
                continue;
            }

            // Remove the * at the beginning of lines
            if (str_starts_with($line, '* ')) {
                $cleanedLine = trim(substr($line, 2));
            } elseif (str_starts_with($line, '*')) {
                $cleanedLine = trim(substr($line, 1));
            } else {
                $cleanedLine = $line;
            }

            // Add non-empty lines to the result
            if ($cleanedLine !== '') {
                $cleanedLines[] = $cleanedLine;
            }
        }

        return $cleanedLines;
    }

    public static function findMethodLines(array $docBlocks, string $methodName): array
    {
        $pattern = '/@method\s+.*?\s+'.preg_quote($methodName, '/').'\s*\(/';
        $matches = [];

        foreach ($docBlocks as $index => $line) {
            if (preg_match($pattern, $line)) {
                $matches[] = $index;
            }
        }

        return $matches;
    }

    public static function buildMethodLine(string $visibility, string $returnType, string $methodName, string $parameters): string
    {
        return "@method {$visibility} {$returnType} {$methodName}({$parameters})";
    }

    public static function formatParameters(array $parameters, array $usings = []): string
    {
        $params = [];

        foreach ($parameters as $parameter) {
            $paramStr = '';

            // Format the type using imports
            if (! empty($parameter->rawType)) {
                $paramStr .= self::formatTypeWithImports($parameter->rawType, $usings).' ';
            }

            // Add parameter name
            $paramStr .= '$'.$parameter->name;

            // Add default value if exists
            if ($parameter->hasDefaultValue) {
                $paramStr .= ' = '.self::formatDefaultValue($parameter->defaultValue);
            }

            $params[] = $paramStr;
        }

        return implode(', ', $params);
    }

    public static function formatReturnType(array $returnTypes, array $usings = []): string
    {
        if (empty($returnTypes)) {
            return 'mixed';
        }

        $types = [];
        foreach ($returnTypes as $returnType) {
            // Use formatTypeWithImports for consistency
            $formattedType = self::formatSingleType($returnType, $usings);

            if ($returnType->isNullable && $returnType->name !== 'null') {
                $types[] = $formattedType;
                $types[] = 'null';
            } else {
                $types[] = $formattedType;
            }
        }

        // Remove duplicates and join
        $types = array_unique($types);

        return implode('|', $types);
    }

    /**
     * Format a raw type string considering imports
     */
    public static function formatTypeWithImports(string $rawType, array $usings = []): string
    {
        // Handle nullable types
        $isNullable = str_starts_with($rawType, '?');
        $cleanType = ltrim($rawType, '?');

        // Handle union types
        if (str_contains($cleanType, '|')) {
            $types = explode('|', $cleanType);
            $formattedTypes = [];

            foreach ($types as $type) {
                $type = trim($type);
                $formattedTypes[] = self::formatSingleTypeString($type, $usings);
            }

            $result = implode('|', $formattedTypes);

            return $isNullable ? '?'.$result : $result;
        }

        // Single type
        $formatted = self::formatSingleTypeString($cleanType, $usings);

        return $isNullable ? '?'.$formatted : $formatted;
    }

    /**
     * Format a single type string
     */
    private static function formatSingleTypeString(string $type, array $usings = []): string
    {
        // Check if it's a built-in type
        $builtInTypes = ['string', 'int', 'float', 'bool', 'array', 'object', 'mixed', 'void', 'null', 'callable', 'iterable', 'self', 'parent', 'static'];
        if (in_array(strtolower($type), $builtInTypes)) {
            return $type;
        }

        // If it doesn't contain a namespace separator, check if it's already imported
        if (! str_contains($type, '\\')) {
            return $type;
        }

        // Extract namespace and class name
        $parts = explode('\\', $type);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);

        // Remove leading backslash if present
        $namespace = ltrim($namespace, '\\');

        // Check if this type is imported
        foreach ($usings as $using) {
            if ($using instanceof RelationDto) {
                $importedNamespace = ltrim($using->namespace ?? '', '\\');

                // Check for exact match
                if ($using->name === $className && $importedNamespace === $namespace) {
                    // Use alias if available, otherwise use the class name
                    return $using->alias ?? $className;
                }
            }
        }

        // Not imported, return with leading backslash
        return '\\'.$type;
    }

    /**
     * Format a single RelationDto type
     */
    private static function formatSingleType(RelationDto $type, array $usings = []): string
    {
        // Built-in types don't need formatting
        if ($type->isBuiltIn) {
            return $type->name;
        }

        // Build the full type name
        $fullType = '';
        if ($type->namespace) {
            $fullType = $type->namespace.'\\'.$type->name;
        } else {
            $fullType = $type->name;
        }

        return self::formatSingleTypeString($fullType, $usings);
    }

    public static function formatDefaultValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            return "'".addslashes($value)."'";
        }
        if (is_array($value)) {
            return '[]';
        }

        return (string) $value;
    }
}
