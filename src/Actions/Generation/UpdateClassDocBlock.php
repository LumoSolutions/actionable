<?php

namespace LumoSolutions\Actionable\Actions\Generation;

use LumoSolutions\Actionable\Dtos\Generation\DocBlockUpdateDto;
use LumoSolutions\Actionable\Traits\IsRunnable;

class UpdateClassDocBlock
{
    use IsRunnable;

    public function handle(DocBlockUpdateDto $dto, bool $dryRun = false): array|bool
    {
        if ($dto->currentDocBlocks == $dto->newDocBlocks) {
            return $dryRun ? [] : false;
        }

        if ($dryRun) {
            return $this->calculateDiff($dto->currentDocBlocks, $dto->newDocBlocks);
        }

        return $this->updateFile($dto);
    }

    protected function calculateDiff(array $current, array $new): array
    {
        $diff = [];

        $removed = array_diff($current, $new);
        foreach ($removed as $line) {
            $diff[] = [
                'type' => '-',
                'line' => $line,
            ];
        }

        $added = array_diff($new, $current);
        foreach ($added as $line) {
            $diff[] = [
                'type' => '+',
                'line' => $line,
            ];
        }

        return $diff;
    }

    protected function updateFile(DocBlockUpdateDto $dto): bool
    {
        $fileContent = file_get_contents($dto->filePath);

        $classPattern = '/^([ \t]*)((?:abstract\s+|final\s+)?class\s+'.preg_quote($dto->className, '/').'\b)/m';

        preg_match($classPattern, $fileContent, $matches, PREG_OFFSET_CAPTURE);
        $classDeclarationOffset = $matches[0][1];
        $indentation = $matches[1][0];

        $newDocBlock = $this->buildDocBlock($dto->newDocBlocks, $indentation);
        $newDocBlock = $newDocBlock !== null
            ? $newDocBlock."\n".$indentation
            : $indentation;

        $existingDocBlockPattern = '/(\/\*\*.*?\*\/)\s*\n\s*(?=(?:abstract\s+|final\s+)?class\s+'.preg_quote($dto->className, '/').'\b)/s';

        if (preg_match($existingDocBlockPattern, $fileContent, $docBlockMatch, PREG_OFFSET_CAPTURE)) {
            // Replace existing docblock
            $fileContent = substr_replace(
                $fileContent,
                $newDocBlock,
                $docBlockMatch[0][1],
                strlen($docBlockMatch[0][0])
            );
        } else {
            $fileContent = substr_replace(
                $fileContent,
                $newDocBlock,
                $classDeclarationOffset,
                0
            );
        }

        return file_put_contents($dto->filePath, $fileContent) !== false;
    }

    protected function buildDocBlock(array $docBlockLines, string $indentation): ?string
    {
        if (empty($docBlockLines)) {
            return null;
        }

        $lines = [];

        $firstLine = trim($docBlockLines[0]);
        if (str_starts_with($firstLine, '/**')) {
            $firstLine = trim(substr($firstLine, 3));
        }

        if (str_ends_with($firstLine, '*/')) {
            $firstLine = trim(substr($firstLine, 0, -2));
        }

        if (! empty($firstLine) && ! str_starts_with($firstLine, '@')) {
            $line = $indentation.'/** '.$firstLine;
            if (count($docBlockLines) == 1) {
                $line .= ' */';

                return $line;
            }
            $lines[] = $line;
        } elseif (! empty($firstLine)) {
            $lines[] = $indentation.'/** ';
            $lines[] = $indentation.' * '.$firstLine;
        } else {
            $lines[] = $indentation.'/** ';
        }
        $startIndex = 1;

        for ($i = $startIndex; $i < count($docBlockLines); $i++) {
            $trimmedLine = trim($docBlockLines[$i]);

            if (str_starts_with($trimmedLine, '/**')) {
                $trimmedLine = trim(substr($trimmedLine, 3));
            }
            if (str_ends_with($trimmedLine, '*/')) {
                $trimmedLine = trim(substr($trimmedLine, 0, -2));
            }

            if (! empty($trimmedLine)) {
                $lines[] = $indentation.' * '.$trimmedLine;
            }
        }

        $lines[] = $indentation.' */';

        return implode("\n", $lines);
    }
}
