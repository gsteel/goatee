<?php

declare(strict_types=1);

namespace GSteel\TemplateStringTest\Expression;

use GSteel\TemplateString\Expression\ExpressionLexer;
use GSteel\TemplateString\Expression\SyntaxError;
use GSteel\TemplateString\Expression\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;
use function Psl\Vec\map;
use function sprintf;

final class ExpressionLexerTest extends TestCase
{
    /** @return list<array{0: string, 1: string}> */
    public static function basicLexerExpectations(): array
    {
        return [
            [
                'some.var',
                'Symbol[1:0]"some.var"',
            ],
            [
                "\n\nsome.var",
                'Symbol[3:0]"some.var"',
            ],
            [
                "\n some.var \n\n",
                'Symbol[2:1]"some.var"',
            ],
            [
                'foo(arg) | fred ',
                'Symbol[1:0]"foo", LParen[1:3]"(", Symbol[1:4]"arg", RParen[1:7]")", Pipe[1:9]"|", Symbol[1:11]"fred"',
            ],
            [
                'foo(arg1, arg2)',
                'Symbol[1:0]"foo", LParen[1:3]"(", Symbol[1:4]"arg1", Symbol[1:10]"arg2", RParen[1:14]")"',
            ],
            [
                '||',
                'Pipe[1:0]"|", Pipe[1:1]"|"',
            ],
            [
                'foo foo',
                'Symbol[1:0]"foo", Symbol[1:4]"foo"',
            ],
        ];
    }

    /** @return array<string, array{0: string}> */
    public static function invalidInputProvider(): array
    {
        return [
            'Symbol containing non-ascii codepoints' => [
                'foo' . mb_chr(0x200B) . 'foo',
            ],
            'Unsupported Comments' => [
                'some.var /* Comment */',
            ],
        ];
    }

    #[DataProvider('basicLexerExpectations')]
    public function testBasicLexing(string $expression, string $expect): void
    {
        $lexer = new ExpressionLexer(0, 1);
        self::assertSame(
            $expect,
            $this->stringify($lexer->lex($expression)),
        );
    }

    #[DataProvider('invalidInputProvider')]
    public function testExceptionThrownForInvalidInput(string $input): void
    {
        $lexer = new ExpressionLexer(0, 1);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageIsOrContains('Syntax error. Unexpected character');

        iterator_to_array($lexer->lex($input));
    }

    /** @param iterable<Token> $tokens */
    private function stringify(iterable $tokens): string
    {
        return implode(', ', map($tokens, $this->tokenToString(...)));
    }

    private function tokenToString(Token $token): string
    {
        return sprintf(
            '%s[%d:%d]"%s"',
            $token->type->name,
            $token->line,
            $token->pos,
            $token->value,
        );
    }
}
