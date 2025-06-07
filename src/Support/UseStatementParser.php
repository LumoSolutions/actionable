<?php

namespace LumoSolutions\Actionable\Support;

use LumoSolutions\Actionable\Dtos\Class\RelationDto;

class UseStatementParser
{
    private const string USE_STATEMENT_PATTERN = '/^use\s+([^;]+);/';

    private const string CLASS_DECLARATION_PATTERN = '/^\s*(abstract\s+|final\s+)?(class|interface|enum|trait)\s+/i';

    private const string ALIASED_IMPORT_PATTERN = '/^(.+)\s+as\s+(.+)$/i';

    private const string GROUPED_USE_PATTERN = '/^(.+)\{(.+)}$/';

    /**
     * Get all use statements (includes) from a PHP class file.
     *
     * @param  string  $filePath  Path to the PHP file
     * @return RelationDto[]
     */
    public function getUseStatements(string $filePath): array
    {
        $fileContent = $this->readFileLines($filePath);

        return $this->parseUseStatements($fileContent);
    }

    /**
     * Read and validate the file content.
     */
    private function readFileLines(string $filePath): ?array
    {
        $content = file(
            $filePath,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        return $content !== false ? $content : null;
    }

    /**
     * Parse use statements from file content.
     *
     * @return RelationDto[]
     */
    private function parseUseStatements(array $lines): array
    {
        $relations = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($this->shouldSkipLine($trimmedLine)) {
                continue;
            }

            if ($this->isClassDeclaration($trimmedLine)) {
                break;
            }

            if ($this->isUseStatement($trimmedLine)) {
                $useStatements = $this->extractUseStatement($trimmedLine);
                $relations = array_merge($relations, $useStatements);
            }
        }

        return $relations;
    }

    /**
     * Check if a line should be skipped during parsing.
     */
    private function shouldSkipLine(string $line): bool
    {
        if (empty($line)) {
            return true;
        }

        $commentPrefixes = ['//', '*', '/*'];
        foreach ($commentPrefixes as $prefix) {
            if (str_starts_with($line, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a line contains a class declaration.
     */
    private function isClassDeclaration(string $line): bool
    {
        return preg_match(self::CLASS_DECLARATION_PATTERN, $line) === 1;
    }

    /**
     * Check if a line contains a use statement.
     */
    private function isUseStatement(string $line): bool
    {
        return preg_match(self::USE_STATEMENT_PATTERN, $line) === 1;
    }

    /**
     * Extract and parse use statements from a line.
     *
     * @return RelationDto[]
     */
    private function extractUseStatement(string $line): array
    {
        if (! preg_match(self::USE_STATEMENT_PATTERN, $line, $matches)) {
            return [];
        }

        $useStatement = trim($matches[1]);

        // Handle grouped use statements: use Namespace\{ClassA, ClassB as B};
        if ($this->isGroupedUseStatement($useStatement)) {
            return $this->parseGroupedUseStatement($useStatement);
        }

        // Handle multiple imports in one line: use A, B, C;
        if (str_contains($useStatement, ',')) {
            return $this->parseMultipleUseStatements($useStatement);
        }

        // Handle single use statement
        $relation = $this->parseSingleUseStatement($useStatement);

        return $relation ? [$relation] : [];
    }

    /**
     * Check if a use statement is grouped (contains curly braces).
     */
    private function isGroupedUseStatement(string $useStatement): bool
    {
        return preg_match(self::GROUPED_USE_PATTERN, $useStatement) === 1;
    }

    /**
     * Parse grouped use statements like: Namespace\{ClassA, ClassB as B} or {DateTime, DateTimeImmutable}
     *
     * @return RelationDto[]
     */
    private function parseGroupedUseStatement(string $useStatement): array
    {
        if (! preg_match(self::GROUPED_USE_PATTERN, $useStatement, $matches)) {
            return [];
        }

        $baseNamespace = trim($matches[1], '\\');
        $groupedImports = trim($matches[2]);

        $relations = [];
        $imports = array_map('trim', explode(',', $groupedImports));

        foreach ($imports as $import) {
            // If there's no base namespace (global namespace), use the import as-is
            $fullName = empty($baseNamespace) ? $import : $baseNamespace.'\\'.$import;
            $relation = $this->parseSingleUseStatement($fullName);
            if ($relation) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    /**
     * Parse multiple use statements separated by commas.
     *
     * @return RelationDto[]
     */
    private function parseMultipleUseStatements(string $useStatement): array
    {
        $imports = array_map('trim', explode(',', $useStatement));
        $relations = [];

        foreach ($imports as $import) {
            $relation = $this->parseSingleUseStatement($import);
            if ($relation) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    /**
     * Parse a single use statement and return a RelationDto.
     */
    private function parseSingleUseStatement(string $useStatement): ?RelationDto
    {
        $useStatement = trim($useStatement);

        // Handle aliased imports: Full\Namespace\Class as Alias
        if (preg_match(self::ALIASED_IMPORT_PATTERN, $useStatement, $matches)) {
            $fullName = trim($matches[1]);
            $alias = trim($matches[2]);

            return $this->createRelationFromFullName($fullName, $alias);
        }

        // Handle regular imports: Full\Namespace\Class
        return $this->createRelationFromFullName($useStatement, null);
    }

    /**
     * Create a RelationDto from a full class name and optional alias.
     */
    private function createRelationFromFullName(string $fullName, ?string $alias): ?RelationDto
    {
        $fullName = trim($fullName, '\\');

        if (empty($fullName)) {
            return null;
        }

        [$namespace, $className] = $this->splitType($fullName);

        return new RelationDto(
            name: $className,
            namespace: $namespace,
            alias: $alias,
            isNullable: false,
            isBuiltIn: false
        );
    }

    /**
     * Split a full type name into namespace and class name.
     *
     * @return array{string|null, string}
     */
    private function splitType(string $fullName): array
    {
        if (! str_contains($fullName, '\\')) {
            return [null, $fullName];
        }

        $parts = explode('\\', $fullName);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);

        return [$namespace, $className];
    }
}
