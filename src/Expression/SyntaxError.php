<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression;

use GSteel\TemplateString\SourceError;

use function sprintf;

final class SyntaxError extends SourceError
{
    /**
     * @param positive-int $line
     * @param non-negative-int $pos
     */
    public static function invalidCharacter(string $byte, int $line, int $pos): self
    {
        return new self(
            sprintf(
                'Syntax error. Unexpected character "%s" on line %d and column %d',
                $byte,
                $line,
                $pos,
            ),
            $line,
            $pos,
        );
    }
}
