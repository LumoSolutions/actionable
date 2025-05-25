<?php

namespace LumoSolutions\Actionable\Tests\Setup\Dtos;

use DateTime;
use LumoSolutions\Actionable\Attributes\DateFormat;
use LumoSolutions\Actionable\Traits\ArrayConvertible;

readonly class DateFormatDto
{
    use ArrayConvertible;

    public function __construct(
        #[DateFormat('Y-m-d')]
        public DateTime $eventDate,
        #[DateFormat('d/m/Y H:i')]
        public DateTime $createdAt,
        #[DateFormat]
        public DateTime $updatedAt,
    ) {}
}
