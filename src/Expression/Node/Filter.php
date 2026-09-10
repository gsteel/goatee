<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression\Node;

final readonly class Filter implements Node
{
    /**
     * @param non-negative-int $pos
     * @param positive-int $line
     */
    public function __construct(
        public string $name,
        public int $pos,
        public int $line,
    ) {}
}
