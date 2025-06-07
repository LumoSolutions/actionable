<?php

namespace LumoSolutions\Actionable\Support;

use InvalidArgumentException;
use LumoSolutions\Actionable\Dtos\Class\MetadataDto;
use LumoSolutions\Actionable\Dtos\Class\RelationDto;
use LumoSolutions\Actionable\Dtos\MethodDto;
use LumoSolutions\Actionable\Dtos\ParameterDto;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class ClassAnalyser
{
    public function __construct(
        private readonly UseStatementParser $fileService
    ) {}

    /**
     * Analyse a class and return its associated metadata.
     *
     * @param  string  $className  Fully qualified class name
     *
     * @throws InvalidArgumentException|ReflectionException if the class does not exist or cannot be reflected
     */
    public function analyse(string $className): MetadataDto
    {
        $reflection = $this->getReflection($className);

        return new MetadataDto(
            className: $reflection->getShortName(),
            namespace: $reflection->getNamespaceName() ?: '',
            filePath: $reflection->getFileName() ?: '',
            docBlock: $reflection->getDocComment() ?: null,
            extends: $this->getParentClass($reflection),
            includes: $this->getIncludes($reflection),
            traits: $this->getTraits($reflection),
            methods: $this->getMethods($reflection)
        );
    }

    /**
     * Get a ReflectionClass instance for the given class name.
     *
     * @param  string  $className  Fully qualified class name
     *
     * @throws InvalidArgumentException|ReflectionException if the class does not exist or cannot be reflected
     */
    private function getReflection(string $className): ReflectionClass
    {
        return rescue(
            fn () => new ReflectionClass($className),
            fn ($e) => null,
        );
    }

    /**
     * Get the parent class of the given ReflectionClass.
     */
    public function getParentClass(ReflectionClass $reflection): ?RelationDto
    {
        $parentClass = $reflection->getParentClass();
        if (! $parentClass) {
            return null;
        }

        return new RelationDto(
            name: $parentClass->getShortName(),
            namespace: $parentClass->getNamespaceName(),
        );
    }

    /**
     * Get all use statements (includes) from the class file.
     *
     * @return RelationDto[]
     */
    public function getIncludes(ReflectionClass $reflection): array
    {
        $filePath = $reflection->getFileName();
        if (! $filePath) {
            return [];
        }

        return $this->fileService->getUseStatements($filePath);
    }

    /**
     * Get all traits used by the given ReflectionClass.
     *
     * @return RelationDto[]
     */
    public function getTraits(ReflectionClass $reflection): array
    {
        $traits = [];
        foreach ($reflection->getTraits() as $trait) {
            $traits[] = new RelationDto(
                name: $trait->getShortName(),
                namespace: $trait->getNamespaceName(),
            );
        }

        return $traits;
    }

    /**
     * Get all methods of the given ReflectionClass.
     *
     * @return MethodDto[]
     */
    public function getMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            $parameters = [];
            foreach ($method->getParameters() as $index => $param) {
                $rawType = $param->hasType()
                    ? $param->getType()->__toString()
                    : 'mixed';

                $parameters[] = new ParameterDto(
                    name: $param->getName(),
                    rawType: $rawType,
                    types: $this->parseUnionType($rawType),
                    isOptional: $param->isOptional(),
                    isVariadic: $param->isVariadic(),
                    isReference: $param->isPassedByReference(),
                    hasDefaultValue: $param->isDefaultValueAvailable(),
                    defaultValue: $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                    position: $index,
                );
            }

            $rawReturnType = $method->hasReturnType() ? $method->getReturnType()->__toString() : 'mixed';

            // Handle constructors specially - they don't have explicit return types
            if ($method->getName() === '__construct') {
                $rawReturnType = 'void';
            }

            $methods[] = new MethodDto(
                name: $method->getName(),
                rawReturnType: $rawReturnType,
                returnTypes: $this->parseUnionType($rawReturnType),
                visibility: $this->getVisibility($method),
                isStatic: $method->isStatic(),
                isAbstract: $method->isAbstract(),
                isFinal: $method->isFinal(),
                parameters: $parameters
            );
        }

        return $methods;
    }

    /**
     * Get the visibility of a reflection member (property or method).
     *
     * @return string 'public', 'protected', or 'private'
     */
    private function getVisibility(ReflectionProperty|ReflectionMethod $member): string
    {
        return match (true) {
            $member->isPrivate() => 'private',
            $member->isProtected() => 'protected',
            default => 'public'
        };
    }

    /**
     * Parse a union type string and return an array of RelationDto objects.
     *
     * @param  string  $type  The union type string (e.g., "string|int|null")
     * @return RelationDto[]
     */
    private function parseUnionType(string $type): array
    {
        $relations = [];

        // Handle nullable types (e.g., ?string)
        $isNullable = str_starts_with($type, '?');
        $cleanType = ltrim($type, '?');

        // Split union types (e.g., "string|int|null")
        $typeList = explode('|', $cleanType);

        foreach ($typeList as $singleType) {
            $singleType = trim($singleType);

            // Check if it's a built-in type
            $builtInTypes = ['string', 'int', 'float', 'bool', 'array', 'object', 'mixed', 'void', 'null', 'callable', 'iterable'];
            $isBuiltIn = in_array(strtolower($singleType), $builtInTypes);

            // Parse namespace and class name
            $namespace = null;
            $className = $singleType;

            if (! $isBuiltIn) {
                if (str_contains($singleType, '\\')) {
                    $parts = explode('\\', $singleType);
                    $className = array_pop($parts);
                    $namespace = implode('\\', $parts);
                }
            }

            // Determine if this specific type is nullable
            $typeIsNullable = $isNullable || strtolower($singleType) === 'null';

            $relations[] = new RelationDto(
                name: $className,
                namespace: $isBuiltIn ? null : $namespace,
                isNullable: $typeIsNullable,
                isBuiltIn: $isBuiltIn
            );
        }

        return $relations;
    }
}
