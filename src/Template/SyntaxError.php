<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Template;

use GSteel\TemplateString\SourceError;

use function sprintf;

final class SyntaxError extends SourceError
{
    /**
     * @param positive-int $line
     * @param non-negative-int $column
     */
    public static function forUnterminatedExpression(string $expressionBody, int $line, int $column): self
    {
        return new self(
            sprintf(
                'Syntax error. Un-terminated expression on line %d and column %d. Saw "{{%s"',
                $line,
                $column,
                $expressionBody,
            ),
            $line,
            $column,
        );
    }
}
