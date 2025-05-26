<?php

namespace LumoSolutions\Actionable\Services;

use Exception;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class ActionDocBlockService
{
    private array $targetTraits = [
        'LumoSolutions\Actionable\Traits\IsRunnable',
        'LumoSolutions\Actionable\Traits\IsDispatchable',
    ];

    private array $builtInTypes = [
        'string', 'int', 'float', 'bool', 'array',
        'object', 'mixed', 'void', 'null', 'callable',
        'iterable', 'resource', 'never', 'true', 'false',
    ];

    public function processFile(string $filePath, bool $dryRun = false): array
    {
        $content = File::get($filePath);

        // Extract class information
        $classInfo = $this->extractClassInfo($content, $filePath);

        if (! $classInfo) {
            return [
                'processed' => false,
                'reason' => 'Could not extract class information',
                'docBlocks' => [],
            ];
        }

        $traitInfo = $this->analyzeTraits($classInfo['className']);

        if (! $traitInfo['hasTargetTraits']) {
            return [
                'processed' => false,
                'reason' => 'Class does not use IsRunnable or IsDispatchable traits',
                'docBlocks' => [],
            ];
        }

        $methodInfo = $this->analyzeHandleMethod($classInfo['className'], $classInfo['useStatements']);

        if (! $methodInfo) {
            return [
                'processed' => false,
                'reason' => 'Class missing handle method',
                'docBlocks' => [],
            ];
        }

        $docBlocks = $this->generateDocBlocks(
            $traitInfo['hasRunnable'],
            $traitInfo['hasDispatchable'],
            $methodInfo['parameters'],
            $methodInfo['returnType']
        );

        $newContent = $this->updateClassDocBlock($content, $docBlocks);

        if ($newContent === $content) {
            return [
                'processed' => false,
                'reason' => 'Doc blocks already up to date',
                'docBlocks' => [],
            ];
        }

        if (! $dryRun) {
            File::put($filePath, $newContent);
        }

        return [
            'processed' => true,
            'reason' => 'Success',
            'docBlocks' => $docBlocks,
        ];
    }

    private function ensureClassIsLoaded(string $className, string $filePath): void
    {
        if (! class_exists($className, false)) {
            if (File::exists($filePath)) {
                require_once $filePath;
            }
        }
    }

    private function extractClassInfo(string $content, string $filePath): ?array
    {
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        $className = null;
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
        }

        if (! $namespace || ! $className) {
            return null;
        }

        $fullClassName = '\\'.$namespace.'\\'.$className;

        if (! class_exists($fullClassName)) {
            $this->ensureClassIsLoaded($fullClassName, $filePath);
        }

        $useStatements = $this->parseUseStatements($content);

        return [
            'className' => $fullClassName,
            'useStatements' => $useStatements,
        ];
    }

    private function parseUseStatements(string $content): array
    {
        $useStatements = [];

        $beforeClass = strstr($content, 'class ', true) ?: $content;

        if (preg_match_all('/use\s+([^;]+);/', $beforeClass, $matches)) {
            foreach ($matches[1] as $useStatement) {
                $useStatement = trim($useStatement);

                if (preg_match('/^(function|const)\s+/', $useStatement)) {
                    continue;
                }

                if (preg_match('/^(.+?)\s+as\s+(\w+)$/', $useStatement, $aliasMatch)) {
                    $fullName = trim($aliasMatch[1]);
                    $alias = trim($aliasMatch[2]);
                    $useStatements[$alias] = $fullName;
                } else {
                    $parts = explode('\\', $useStatement);
                    $className = end($parts);
                    $useStatements[$className] = $useStatement;
                }
            }
        }

        return $useStatements;
    }

    private function analyzeTraits(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $traits = $reflection->getTraitNames();

        $hasRunnable = in_array($this->targetTraits[0], $traits);
        $hasDispatchable = in_array($this->targetTraits[1], $traits);

        return [
            'hasTargetTraits' => $hasRunnable || $hasDispatchable,
            'hasRunnable' => $hasRunnable,
            'hasDispatchable' => $hasDispatchable,
        ];
    }

    private function analyzeHandleMethod(string $className, array $useStatements): ?array
    {
        try {
            $reflection = new ReflectionClass($className);

            if (! $reflection->hasMethod('handle')) {
                return null;
            }

            $handleMethod = $reflection->getMethod('handle');

            return [
                'parameters' => $this->buildParameterString($handleMethod, $useStatements),
                'returnType' => $this->getReturnTypeString($handleMethod, $useStatements),
            ];
        } catch (Exception) {
            return null;
        }
    }

    private function buildParameterString(ReflectionMethod $method, array $useStatements): string
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $paramString = '';

            // Add type hint
            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    $resolvedType = $this->resolveType($typeName, $useStatements);

                    if ($type->allowsNull() && $typeName !== 'mixed') {
                        $paramString .= '?';
                    }

                    $paramString .= $resolvedType.' ';
                }
            }

            $paramString .= '$'.$param->getName();

            if ($param->isDefaultValueAvailable()) {
                $paramString .= ' = '.$this->formatDefaultValue($param->getDefaultValue());
            }

            $parameters[] = $paramString;
        }

        return implode(', ', $parameters);
    }

    private function getReturnTypeString(ReflectionMethod $method, array $useStatements): string
    {
        if (! $method->hasReturnType()) {
            return 'mixed';
        }

        $returnType = $method->getReturnType();

        if ($returnType instanceof ReflectionNamedType) {
            $typeName = $returnType->getName();
            $resolvedType = $this->resolveType($typeName, $useStatements);

            if ($returnType->allowsNull() && $typeName !== 'mixed') {
                return '?'.$resolvedType;
            }

            return $resolvedType;
        }

        return 'mixed';
    }

    private function resolveType(string $typeName, array $useStatements): string
    {
        if (in_array($typeName, $this->builtInTypes)) {
            return $typeName;
        }

        $cleanTypeName = ltrim($typeName, '\\');

        foreach ($useStatements as $shortName => $fullName) {
            $cleanFullName = ltrim($fullName, '\\');
            if ($cleanFullName === $cleanTypeName) {
                return $shortName;
            }
        }

        return '\\' . $typeName;
    }

    private function formatDefaultValue($value): string
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
            return empty($value) ? '[]' : '[...]';
        }

        return (string) $value;
    }

    private function generateDocBlocks(bool $hasRunnable, bool $hasDispatchable, string $parameters, string $returnType): array
    {
        $docBlocks = [];

        if ($hasRunnable) {
            $docBlocks[] = "@method static {$returnType} run({$parameters})";
        }

        if ($hasDispatchable) {
            $docBlocks[] = "@method static void dispatch({$parameters})";
            $docBlocks[] = "@method static void dispatchOn(string \$queue" . ($parameters ? ", {$parameters}" : "") . ")";
        }

        return $docBlocks;
    }

    private function updateClassDocBlock(string $content, array $docBlocks): string
    {
        $newDocBlockContent = implode("\n * ", $docBlocks);

        $pattern = '/\/\*\*\s*\n(.*?)\*\/\s*\n((?:(?:abstract|final)\s+)?class\s+\w+)/s';

        if (preg_match($pattern, $content, $matches)) {
            $existingDocBlock = $matches[1];
            $classDeclaration = $matches[2];

            $cleanedDocBlock = preg_replace(
                '/\s*\*\s*@method\s+static\s+[^\s]+\s+(run|dispatch|dispatchOn)\([^)]*\)\s*\n/m',
                '',
                $existingDocBlock
            );

            $cleanedDocBlock = preg_replace('/(\n\s*\*\s*\n){2,}/', "\n *\n", $cleanedDocBlock);
            $cleanedDocBlock = rtrim($cleanedDocBlock);

            $contentOnly = preg_replace('/[\s*\n\r\t]/', '', $cleanedDocBlock);
            $hasContent = ! empty($contentOnly);

            if ($hasContent) {
                $updatedDocBlock = $cleanedDocBlock."\n *\n * ".$newDocBlockContent."\n";
            } else {
                $updatedDocBlock = "\n * ".$newDocBlockContent."\n";
            }

            return str_replace($matches[0], "/**{$updatedDocBlock} */\n{$classDeclaration}", $content);
        } else {
            $pattern = '/((?:(?:abstract|final)\s+)?class\s+\w+)/';
            $newDocBlock = "/**\n * {$newDocBlockContent}\n */\n$1";

            return preg_replace($pattern, $newDocBlock, $content, 1);
        }
    }
}
