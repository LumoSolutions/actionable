<?php

namespace LumoSolutions\Actionable\Dtos\Generation;

use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class DocBlockUpdateDto
{
    use ArrayConvertible;

    public function __construct(
        public string $filePath,
        public string $className,
        public array $currentDocBlocks = [],
        public array $newDocBlocks = [],
    ) {}
}
