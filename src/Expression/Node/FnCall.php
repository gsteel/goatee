<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression\Node;

use GSteel\TemplateString\Expression\ParseError;

use function preg_match;

final readonly class FnCall implements Node
{
    /**
     * @param non-negative-int $pos
     * @param positive-int $line
     * @param list<Variable> $arguments
     * @throws ParseError
     */
    public function __construct(
        public string $name,
        public int $pos,
        public int $line,
        public array $arguments,
    ) {
        $this->assertValidFunctionName();
    }

    /** @throws ParseError */
    private function assertValidFunctionName(): void
    {
        $result = (bool) preg_match('/^\w+$/i', $this->name);
        if ($result) {
            return;
        }

        throw ParseError::forInvalidFunctionName($this->name, $this->line, $this->pos);
    }
}
