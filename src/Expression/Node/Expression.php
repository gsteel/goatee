<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression\Node;

final readonly class Expression implements Node
{
    /**
     * @param non-negative-int $pos
     * @param positive-int $line
     * @param list<Node> $nodes
     */
    public function __construct(
        public string $name,
        public int $pos,
        public int $line,
        public array $nodes,
    ) {}
}
