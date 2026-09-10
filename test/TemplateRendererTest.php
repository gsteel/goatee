<?php

declare(strict_types=1);

namespace GSteel\TemplateStringTest;

use DateTimeImmutable;
use Exception;
use GSteel\TemplateString\Context;
use GSteel\TemplateString\FilterManager;
use GSteel\TemplateString\FunctionManager;
use GSteel\TemplateString\RenderingFailed;
use GSteel\TemplateString\SourceError;
use GSteel\TemplateString\TemplateRenderer;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;

use function mb_chr;
use function strrev;

final class TemplateRendererTest extends TestCase
{
    private FilterManager $filters;
    private FunctionManager $functions;
    private TemplateRenderer $renderer;

    #[Override]
    protected function setUp(): void
    {
        $this->filters = new FilterManager([]);
        $this->functions = new FunctionManager([]);
        $this->renderer = new TemplateRenderer($this->filters, $this->functions);
    }

    /** @return list<array{0: string, 1: array, 2: string}> */
    public static function basicRenderingProvider(): array
    {
        return [
            [
                'Mary had a little {{ animal }}',
                ['animal' => 'Goat'],
                'Mary had a little Goat',
            ],
            [
                '{{ person.name }}',
                ['person' => ['name' => 'Pants']],
                'Pants',
            ],
            [
                '',
                [],
                '',
            ],
            [
                'Some {bracing} text',
                [],
                'Some {bracing} text',
            ],
        ];
    }

    /** @param array<array-key, mixed> $model */
    #[DataProvider('basicRenderingProvider')]
    public function testBasicRendering(string $template, array $model, string $expect): void
    {
        $result = $this->renderer->render($template, $model);

        self::assertSame($expect, $result);
    }

    public function testBasicFilters(): void
    {
        $this->filters->registerFilter('strrev', static fn (string $string): string => strrev($string));

        self::assertSame(
            'derF',
            $this->renderer->render(
                '{{ name | strrev }}',
                ['name' => 'Fred'],
            ),
        );
    }

    public function testBasicFunctions(): void
    {
        $date = new DateTimeImmutable();
        $expect = $date->format('Y-m-d');

        $this->functions->registerFunction('now', static fn (): DateTimeImmutable => $date);
        $this->filters->registerFilter('fmt', static fn (DateTimeImmutable $input): string => $input->format('Y-m-d'));

        self::assertSame(
            $expect,
            $this->renderer->render(
                '{{ now() | fmt }}',
            ),
        );
    }

    public function testFiltersCanUseTheContext(): void
    {
        $filter = static function (string $input, Context $context): string {
            $value = $context->extract('foo.bar');
            self::assertIsString($value);

            return $input . ' ' . $value;
        };

        $this->filters->registerFilter('bing', $filter);

        self::assertSame(
            'Hey There',
            $this->renderer->render(
                '{{ foo.baz | bing }}',
                [
                    'foo' => [
                        'baz' => 'Hey',
                        'bar' => 'There',
                    ],
                ],
            ),
        );
    }

    /** @return list<array{0: mixed}> */
    public static function thingsThatWillEndUpAsEmptyStrings(): array
    {
        return [
            [true],
            [false],
            [['arrays']],
            [(object)['foo' => 'objects']],
            [static fn (): string => 'callables'],
        ];
    }

    #[DataProvider('thingsThatWillEndUpAsEmptyStrings')]
    public function testModelVariablesThatCannotBeCastToString(mixed $value): void
    {
        $model = ['foo' => $value];
        self::assertSame(
            '',
            $this->renderer->render('{{ foo }}', $model),
        );
    }

    public function testInvalidTemplateIssuesAreUpcastToRenderFailure(): void
    {
        $this->expectException(RenderingFailed::class);
        $this->expectExceptionMessageIsOrContains('Template rendering failed because the template was not valid');

        $this->renderer->render('{{ Oh Bad!');
    }

    public function testMissingFunctionsAreUpcastToRenderFailure(): void
    {
        $this->expectException(RenderingFailed::class);
        $this->expectExceptionMessageIsOrContains('A function could not be found by the name "nope"');

        $this->renderer->render('{{ nope() }}');
    }

    public function testMissingFiltersAreUpcastToRenderFailure(): void
    {
        $this->expectException(RenderingFailed::class);
        $this->expectExceptionMessageIsOrContains('A filter could not be found by the name "Fred"');

        $this->renderer->render('{{ foo.bar | Fred }}');
    }

    public function testExceptionInFunctionIsCaughtAndReThrown(): void
    {
        $this->functions->registerFunction('bad', static fn (): never => throw new Exception('Nope!'));

        $this->expectException(RenderingFailed::class);
        $this->expectExceptionMessageIsOrContains('An exception occurred during rendering: Nope!');

        $this->renderer->render('{{ bad() }}');
    }

    public function testExceptionInFilterIsCaughtAndReThrown(): void
    {
        $this->filters->registerFilter('bad', static fn (): never => throw new Exception('Nope!'));

        $this->expectException(RenderingFailed::class);
        $this->expectExceptionMessageIsOrContains('An exception occurred during rendering: Nope!');

        $this->renderer->render('{{ foo.bar | bad }}');
    }

    public function testStringableObjectsAreCastDuringRender(): void
    {
        $object = new class implements Stringable
        {
            #[Override]
            public function __toString(): string
            {
                return 'Granny';
            }
        };

        self::assertSame(
            'Hi Granny, How are you?',
            $this->renderer->render(
                'Hi {{ name }}, How are you?',
                [
                    'name' => $object,
                ],
            ),
        );
    }

    /** @return array<string, array{0: string}> */
    public static function invalidTemplates(): array
    {
        return [
            'Unterminated Expression' => ['Foo {{ Bar'],
            'Unterminated Expression 2' => ['Foo {{ Bar }'],
            'Symbol containing non-ascii codepoints' => [
                'Hi: {{ foo' . mb_chr(0x200B) . 'foo }}',
            ],
            'Unsupported Comments' => [
                'Hi: {{ some.var /* Comment */ }}',
            ],
            'Too many braces' => [
                'Hi {{{ name }}}!',
            ],
            'Unclosed Paren' => [
                'Foo {{ foo( }} Bar',
            ],
            'Invalid Arg |' => [
                'Foo {{ foo(|) }} Bar',
            ],
            'Invalid Arg (' => [
                'Foo {{ foo(() }} Bar',
            ],
            'Unexpected )' => [
                'Foo {{ foo()) }} Bar',
            ],
            'Invalid Arg: Pipe in args' => [
                'Foo {{ foo(foo | bar) }} Bar',
            ],
            'Standalone pipe' => [
                '{{ | }}',
            ],
            'Trailing pipe' => [
                '{{ foo| }}',
            ],
            'Invalid Variable Name 1' => [
                '{{ foo..bar }}',
            ],
            'Invalid Variable Name 2' => [
                '{{ .foo }}',
            ],
            'Invalid Variable Name 3' => [
                '{{ foo. }}',
            ],
            'Invalid Function Name 1' => [
                '{{ foo.bar() }}',
            ],
            'Function after variable' => [
                '{{ foo foo() }}',
            ],
        ];
    }

    #[DataProvider('invalidTemplates')]
    public function testParsingATemplateWillYieldSourceErrorsForInvalidTemplates(string $template): void
    {
        $this->expectException(SourceError::class);
        $this->renderer->parseTemplate($template);
    }
}
