<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use RuntimeException;

abstract class SourceError extends RuntimeException
{
    /**
     * @param positive-int $sourceLine
     * @param non-negative-int $sourceColumn
     */
    public function __construct(
        string $message,
        public int $sourceLine,
        public int $sourceColumn,
    ) {
        parent::__construct($message);
    }
}
