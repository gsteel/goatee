<?php

declare(strict_types=1);

namespace GSteel\GoateeTest\Expression;

use GSteel\Goatee\Expression\ExpressionParser;
use GSteel\Goatee\Expression\Node\Expression;
use GSteel\Goatee\Expression\Node\Filter;
use GSteel\Goatee\Expression\Node\FnCall;
use GSteel\Goatee\Expression\Node\Node;
use GSteel\Goatee\Expression\Node\Variable;
use GSteel\Goatee\Expression\ParseError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function implode;
use function Psl\Vec\map;
use function sprintf;

final class ExpressionParserTest extends TestCase
{
    /** @return list<array{0: string, 1: string}> */
    public static function basicParseDataProvider(): array
    {
        return [
            ['some.var',                 'V: some.var'],
            [' some.var  ',              'V: some.var'],
            ["\nsome.var  \n",           'V: some.var'],
            ['foo(arg1, arg2) | filter', 'C: foo(V: arg1, V: arg2), F: filter'],
            ['foo()',                    'C: foo()'],
            ['var | filter1 | filter2',  'V: var, F: filter1, F: filter2'],
        ];
    }

    #[DataProvider('basicParseDataProvider')]
    public function testExpressionsAreParsed(string $expression, string $expect): void
    {
        $parser = new ExpressionParser();
        $node = $parser->parse($expression, 1, 0);
        self::assertSame($expect, $this->nodeToString($node));
    }

    private function nodeToString(Node $node): string
    {
        return match ($node::class) {
            Expression::class => sprintf(
                '%s',
                implode(', ', map($node->nodes, $this->nodeToString(...))),
            ),
            Filter::class => sprintf('F: %s', $node->name),
            FnCall::class => sprintf(
                'C: %s(%s)',
                $node->name,
                implode(', ', map($node->arguments, $this->nodeToString(...))),
            ),
            Variable::class => sprintf('V: %s', $node->name),
            default => 'Not Supported',
        };
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function invalidExpressionProvider(): array
    {
        return [
            'Unclosed Paren' => [
                'foo(',
                'Unclosed parenthesis for function call "foo"',
            ],
            'Invalid Arg |' => [
                'foo(|)',
                'Invalid argument to function "foo" on line 1 and column 4. Only symbols are allowed but received Pipe',
            ],
            'Invalid Arg (' => [
                'foo(()',
                'Invalid argument to function "foo" on line 1 and column 4. Only symbols are allowed but received LParen',
            ],
            'Unexpected )' => [
                'foo())',
                'Unexpected token "RParen" on line 1 and column 5',
            ],
            'Invalid Arg: Pipe in args' => [
                'foo(foo | bar)',
                'Invalid argument to function "foo" on line 1 and column 8. Only symbols are allowed but received Pipe',
            ],
            'Standalone pipe' => [
                '|',
                'Invalid filter on line 1 and column 0. Expected symbol but received null',
            ],
            'Trailing pipe' => [
                'foo|',
                'Invalid filter on line 1 and column 3. Expected symbol but received null',
            ],
            'Invalid Variable Name 1' => [
                'foo..bar',
                'Invalid variable name "foo..bar" on line 1 and column 0',
            ],
            'Invalid Variable Name 2' => [
                '.foo',
                'Invalid variable name ".foo" on line 1 and column 0',
            ],
            'Invalid Variable Name 3' => [
                'foo.',
                'Invalid variable name "foo." on line 1 and column 0',
            ],
            'Invalid Function Name 1' => [
                'foo.bar()',
                '"foo.bar" is not a valid function name on line 1 and column 0',
            ],
            'Function after variable' => [
                'foo foo()',
                'Variables can only be function args, stand alone or be followed by a filter',
            ],
        ];
    }

    #[DataProvider('invalidExpressionProvider')]
    public function testInvalidExpressions(string $expression, string $expectedMessage): void
    {
        $parser = new ExpressionParser();
        $this->expectException(ParseError::class);
        $this->expectExceptionMessageIsOrContains($expectedMessage);
        $parser->parse($expression, 1, 0);
    }
}
