<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression;

use Generator;
use GSteel\TemplateString\Expression\Node\Expression;
use GSteel\TemplateString\Expression\Node\Filter;
use GSteel\TemplateString\Expression\Node\FnCall;
use GSteel\TemplateString\Expression\Node\Node;
use GSteel\TemplateString\Expression\Node\Variable;

use function assert;
use function iterator_to_array;

/** @mago-expect lint:halstead */
final readonly class ExpressionParser
{
    /**
     * @param Generator<int, Token> $tokens
     * @return list<Variable>
     * @throws ParseError
     */
    private function collectArguments(Token $fn, Generator $tokens): array {
        $args = [];
        $leftParen = $tokens->current();
        assert($leftParen !== null && $leftParen->type === TokenType::LParen, 'Indicates incorrect usage');
        $tokens->next();

        while ($tokens->valid()) {
            $token = $tokens->current();
            assert($token !== null, 'Impossible null token whilst the iterator is valid');
            if ($token->type === TokenType::RParen) {
                $tokens->next();
                return $args;
            }

            if ($token->type === TokenType::Symbol) {
                $args[] = new Variable($token->value, $token->pos, $token->line);
                $tokens->next();
                continue;
            }

            throw ParseError::withInvalidFnArg($fn, $tokens->current());
        }

        throw ParseError::withDanglingLeftParen($fn, $leftParen);
    }

    /**
     * @param string $expression
     * @param positive-int $startLine
     * @param non-negative-int $startPos
     * @throws SyntaxError
     * @throws ParseError
     */
    public function parse(string $expression, int $startLine, int $startPos): Expression
    {
        $lexer = new ExpressionLexer($startPos, $startLine);
        $ast = $this->build($lexer->lex($expression));

        return new Expression(
            $expression,
            $startPos,
            $startLine,
            iterator_to_array($ast, false),
        );
    }

    /**
     * @param Generator<int, Token> $tokens
     * @return Generator<int, Node>
     * @throws ParseError
     */
    private function build(Generator $tokens): Generator
    {
        while ($tokens->valid()) {
            $token = $tokens->current();
            assert($token instanceof Token, 'Impossible condition');

            if ($token->type === TokenType::Pipe) {
                $tokens->next();
                $next = $tokens->current();

                if ($next === null || $next->type !== TokenType::Symbol) {
                    throw ParseError::withInvalidFilterArgument($token, $next);
                }

                yield new Filter($next->value, $next->pos, $next->line);
                $tokens->next();
                continue;
            }

            if ($token->type === TokenType::Symbol) {
                $tokens->next();
                $next = $tokens->current();
                if ($next === null) {
                    yield new Variable($token->value, $token->pos, $token->line);
                    break;
                }

                if ($next->type === TokenType::LParen) {
                    yield new FnCall(
                        $token->value,
                        $token->pos,
                        $token->line,
                        $this->collectArguments($token, $tokens),
                    );
                    continue;
                }

                if ($next->type !== TokenType::Pipe) {
                    throw ParseError::onlyPipesCanFollowVariables($token, $next);
                }

                yield new Variable($token->value, $token->pos, $token->line);
                continue;
            }

            throw ParseError::unexpectedToken($token);
        }
    }
}
