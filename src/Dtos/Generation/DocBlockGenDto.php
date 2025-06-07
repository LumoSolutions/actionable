<?php

namespace LumoSolutions\Actionable\Dtos\Generation;

use LumoSolutions\Actionable\Attributes\ArrayOf;
use LumoSolutions\Actionable\Dtos\Class\RelationDto;
use LumoSolutions\Actionable\Dtos\MethodDto;
use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class DocBlockGenDto
{
    use ArrayConvertible;

    public function __construct(
        public bool $isRunnable,
        public bool $isDispatchable,
        public ?MethodDto $handle,
        public array $docBlocks = [],

        #[ArrayOf(RelationDto::class)]
        public array $usings = [],
    ) {}
}
