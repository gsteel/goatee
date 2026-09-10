<?php

declare(strict_types=1);

namespace GSteel\GoateeTest\Template;

use GSteel\Goatee\Template\SyntaxError;
use GSteel\Goatee\Template\TemplateLexer;
use GSteel\Goatee\Template\Token;
use GSteel\Goatee\Template\TokenType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function implode;
use function Psl\Vec\map;
use function strlen;

final class TemplateLexerTest extends TestCase
{
    /** @return list<array{0: string, 1: string}> */
    public static function basicDataProvider(): array
    {
        return [
            ['Some {{ text }} Here',               'S[5], E[6], S[5]'],
            ['Some {{text}} Here',                 'S[5], E[4], S[5]'],
            ['{{ start }} Here',                   'E[7], S[5]'],
            ['End {{ there }}',                    'S[4], E[7]'],
            ["\nSome \n{{\n\ntext\n\n}}\n Here\n", 'S[7], E[8], S[7]'],
            ['Whatever…',                          'S[11]'],
            ['Emoji 👍',                           'S[10]'],
            ['👍{{👍}}',                           'S[4], E[4]'],
            ['this }} is ok',                      'S[13]'],
        ];
    }

    #[DataProvider('basicDataProvider')]
    public function testBasicLexing(string $input, string $expect): void
    {
        $lexer = new TemplateLexer();
        $tokens = $lexer->lex($input);
        self::assertSame($expect, $this->stringify($tokens));
    }

    /** @return list<array{0: string, 1: string}> */
    public static function invalidTemplates(): array
    {
        return [
            [
                'This {{ is bad',
                'Syntax error. Un-terminated expression on line 1 and column 5',
            ],
            [
                "\n this\n is\n also\n  {{\nbad stuff",
                'Syntax error. Un-terminated expression on line 5 and column 2',
            ],
            [
                '{{ still bad',
                'Syntax error. Un-terminated expression on line 1 and column 0',
            ],
        ];
    }

    #[DataProvider('invalidTemplates')]
    public function testUnterminatedExpressionsAreExceptional(string $input, string $expectMessage): void
    {
        $lexer = new TemplateLexer();
        $tokens = $lexer->lex($input);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageIsOrContains($expectMessage);
        $this->stringify($tokens);
    }

    /** @param iterable<Token> $tokens */
    private function stringify(iterable $tokens): string
    {
        return implode(', ', map(
            $tokens,
            $this->tokenToString(...),
        ));
    }

    private function tokenToString(Token $token): string
    {
        return match ($token->type) {
            TokenType::String => sprintf('S[%d]', strlen($token->value)),
            TokenType::Expression => sprintf('E[%d]', strlen($token->value)),
        };
    }
}
