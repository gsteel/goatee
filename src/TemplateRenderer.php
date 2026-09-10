<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use GSteel\Goatee\Expression\ExpressionParser;
use GSteel\Goatee\Expression\Node\Expression;
use GSteel\Goatee\Expression\Node\Filter;
use GSteel\Goatee\Expression\Node\FnCall;
use GSteel\Goatee\Expression\Node\Node;
use GSteel\Goatee\Expression\Node\StringLiteral;
use GSteel\Goatee\Expression\Node\Variable;
use GSteel\Goatee\Template\TemplateLexer;
use GSteel\Goatee\Template\TokenType;
use Stringable;
use Throwable;

use function is_bool;
use function is_scalar;
use function Psl\Vec\map;

/**
 * @mago-expect lint:halstead,cyclomatic-complexity,kan-defect (Improvements could be made here!)
 */
final readonly class TemplateRenderer
{
    private Options $options;

    public function __construct(
        private FilterManager $filters,
        private FunctionManager $functions,
        Options|null $options = null,
    ) {
        $this->options = $options ?? new Options();
    }

    /**
     * @param array<array-key, mixed>|object $model
     * @throws RenderingFailed
     */
    public function render(string $template, array|object $model = []): string
    {
        $context = new Context($model);
        $buffer = '';

        try {
            $nodes = $this->parseTemplate($template);
            foreach ($nodes as $node) {
                $buffer .= match ($node::class) {
                    StringLiteral::class => $node->name,
                    Expression::class => $this->handleExpression($node, $context),
                    default => '',
                };
            }
        } catch (SourceError $error) {
            throw RenderingFailed::becauseOfAnInvalidTemplate($error);
        }

        return $buffer;
    }

    /**
     * @return list<Node>
     * @throws SourceError
     */
    public function parseTemplate(string $template): array
    {
        $templateLexer = new TemplateLexer();
        $tokens = $templateLexer->lex($template);

        $expressionParser = new ExpressionParser();

        $nodes = [];
        foreach ($tokens as $token) {
            $nodes[] = match ($token->type) {
                TokenType::String => new StringLiteral($token->value, $token->pos, $token->line),
                TokenType::Expression => $expressionParser->parse(
                    $token->value,
                    $token->line,
                    $token->pos,
                ),
            };
        }

        return $nodes;
    }

    /**
     * @throws RenderingFailed
     * @mago-expect analysis:mixed-assignment
     */
    private function handleExpression(Expression $expression, Context $context): string
    {
        $buffer = '';

        foreach ($expression->nodes as $node) {
            if ($node instanceof Variable) {
                $buffer = $context->extract($node->name);
            }

            if ($node instanceof FnCall) {
                try {
                    $output = $this->callFunction($node, $context);
                    $buffer = $output;
                } catch (FunctionNotFound $error) {
                    if (! $this->options->skipMissingFunctions) {
                        throw RenderingFailed::becauseAFunctionCouldNotBeFound($error);
                    }
                } catch (Throwable $error) {
                    throw RenderingFailed::becauseOfAnUnknownError($error);
                }
            }

            if ($node instanceof Filter) {
                try {
                    $filtered = $this->callFilter($node, $buffer, $context);
                    $buffer = $filtered;
                } catch (FilterNotFound $error) {
                    if (! $this->options->skipMissingFilters) {
                        throw RenderingFailed::becauseAFilterCouldNotBeFound($error);
                    }
                } catch (Throwable $error) {
                    throw RenderingFailed::becauseOfAnUnknownError($error);
                }
            }
        }

        if (is_bool($buffer) || $buffer === null) {
            return '';
        }

        if (is_scalar($buffer) || $buffer instanceof Stringable) {
            return (string) $buffer;
        }

        return '';
    }

    /** @throws FunctionNotFound */
    private function callFunction(FnCall $node, Context $context): mixed
    {
        $args = map(
            $node->arguments,
            static fn (Variable $variable): mixed => $context->extract($variable->name),
        );

        $function = $this->functions->get($node->name);

        return $function(...$args);
    }

    /** @throws FilterNotFound */
    private function callFilter(Filter $node, mixed $buffer, Context $context): mixed
    {
        $filter = $this->filters->get($node->name);

        return $filter($buffer, $context);
    }
}
