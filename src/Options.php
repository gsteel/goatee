<?php

declare(strict_types=1);

namespace GSteel\Goatee;

final readonly class Options
{
    public function __construct(
        public bool $skipMissingFilters = false,
        public bool $skipMissingFunctions = false,
        public bool $strictVariables = false,
    ) {}
}
