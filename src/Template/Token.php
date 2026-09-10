<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Template;

final readonly class Token
{
    /**
     * @param TokenType $type
     * @param string $value
     * @param non-negative-int $pos
     * @param positive-int $line
     */
    public function __construct(
        public TokenType $type,
        public string $value,
        public int $pos,
        public int $line,
    ) {
    }
}
