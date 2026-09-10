<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression\Node;

use GSteel\TemplateString\Expression\ParseError;

use function explode;
use function preg_match;

final readonly class Variable implements Node
{
    /**
     * @param non-negative-int $pos
     * @param positive-int $line
     * @throws ParseError
     */
    public function __construct(
        public string $name,
        public int $pos,
        public int $line,
    ) {
        $this->assertValidVariableName();
    }

    /** @throws ParseError */
    private function assertValidVariableName(): void
    {
        $parts = explode('.', $this->name);
        foreach ($parts as $part) {
            if ($part !== '' && (bool) preg_match('/^[\w+]/i', $part)) {
                continue;
            }

            throw ParseError::forInvalidVariableName($this->name, $this->line, $this->pos);
        }
    }
}
