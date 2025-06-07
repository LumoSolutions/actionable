<?php

namespace LumoSolutions\Actionable\Support;

class DocBlockProcessor
{
    private array $docBlocks;

    public function __construct(array $docBlocks)
    {
        $this->docBlocks = $docBlocks;
    }

    public function removeMethodsIf(string $methodName, bool $condition): void
    {
        if ($condition) {
            $this->removeMethod($methodName);
        }
    }

    public function removeMethod(string $methodName): void
    {
        $indices = DocBlockHelper::findMethodLines($this->docBlocks, $methodName);

        // Remove in reverse order to maintain indices
        rsort($indices);
        foreach ($indices as $index) {
            unset($this->docBlocks[$index]);
        }

        // Re-index array
        $this->docBlocks = array_values($this->docBlocks);
    }

    public function addOrReplaceMethod(string $methodName, ?string $methodLine): void
    {
        if ($methodLine === null) {
            return;
        }

        $existingIndices = DocBlockHelper::findMethodLines($this->docBlocks, $methodName);

        if (! empty($existingIndices)) {
            // Replace the first occurrence
            $this->docBlocks[$existingIndices[0]] = $methodLine;

            // Remove any additional occurrences
            for ($i = 1; $i < count($existingIndices); $i++) {
                unset($this->docBlocks[$existingIndices[$i]]);
            }

            // Re-index array
            $this->docBlocks = array_values($this->docBlocks);
        } else {
            // Add new method line
            $this->docBlocks[] = $methodLine;
        }
    }

    public function getDocBlocks(): array
    {
        return $this->docBlocks;
    }
}
