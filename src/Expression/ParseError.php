<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression;

use GSteel\TemplateString\SourceError;

use function sprintf;

final class ParseError extends SourceError
{
    /**
     * @param positive-int $line
     * @param non-negative-int $pos
     */
    public static function forInvalidFunctionName(string $name, int $line, int $pos): self
    {
        return new self(
            sprintf(
                '"%s" is not a valid function name on line %d and column %d',
                $name,
                $line,
                $pos,
            ),
            $line,
            $pos,
        );
    }

    public static function withDanglingLeftParen(Token $symbol, Token $leftParen): self
    {
        return new self(
            sprintf(
                'Unclosed parenthesis for function call "%s" on line %d and column %d',
                $symbol->value,
                $leftParen->line,
                $leftParen->pos,
            ),
            $leftParen->line,
            $leftParen->pos,
        );
    }

    public static function withInvalidFnArg(Token $fn, Token $arg): self
    {
        return new self(
            sprintf(
                'Invalid argument to function "%s" on line %d and column %d. Only symbols are allowed but received %s',
                $fn->value,
                $arg->line,
                $arg->pos,
                $arg->type->name,
            ),
            $arg->line,
            $arg->pos,
        );
    }

    public static function withInvalidFilterArgument(Token $pipe, Token|null $next): self
    {
        $pos = $next ?? $pipe;

        return new self(
            sprintf(
                'Invalid filter on line %d and column %d. Expected symbol but received %s',
                $pos->line,
                $pos->pos,
                $next === null ? 'null' : $next->type->name,
            ),
            $pos->line,
            $pos->pos,
        );
    }

    public static function onlyPipesCanFollowVariables(Token $variable, Token $next): self
    {
        return new self(
            sprintf(
                'Variables can only be function args, stand alone or be followed by a filter. The variable "%s" is '
                . 'followed by a %s on line %d and column %d',
                $variable->value,
                $next->type->name,
                $next->line,
                $next->pos,
            ),
            $next->line,
            $next->pos,
        );
    }

    /**
     * @param positive-int $line
     * @param non-negative-int $pos
     */
    public static function forInvalidVariableName(string $name, int $line, int $pos): self
    {
        return new self(
            sprintf(
                'Invalid variable name "%s" on line %d and column %d',
                $name,
                $line,
                $pos,
            ),
            $line,
            $pos,
        );
    }

    public static function unexpectedToken(Token $token): self
    {
        return new self(
            sprintf(
                'Unexpected token "%s" on line %d and column %d',
                $token->type->name,
                $token->line,
                $token->pos,
            ),
            $token->line,
            $token->pos,
        );
    }
}
