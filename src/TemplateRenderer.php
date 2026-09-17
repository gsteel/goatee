<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use GSteel\Goatee\Expression\ExpressionParser;
use GSteel\Goatee\Expression\Node\Expression;
use GSteel\Goatee\Expression\Node\Filter;
use GSteel\Goatee\Expression\Node\FnCall;
use GSteel\Goatee\Expression\Node\StringLiteral;
use GSteel\Goatee\Expression\Node\Variable;
use GSteel\Goatee\Template\TemplateLexer;
use GSteel\Goatee\Template\TokenType;
use Override;
use Stringable;
use Throwable;

use function is_bool;
use function is_scalar;
use function Psl\Vec\map;

/**
 * @mago-expect lint:cyclomatic-complexity,kan-defect (Improvements could be made here!)
 */
final readonly class TemplateRenderer implements Renderer, TemplateParser
{
    private Options $options;

    public function __construct(
        private FilterManager $filters,
        private FunctionManager $functions,
        Options|null $options = null,
    ) {
        $this->options = $options ?? new Options();
    }

    /** @inheritDoc */
    #[Override]
    public function render(string $template, array|object $model = []): string
    {
        $context = new Context($model, $this->options->strictVariables);
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

    /** @inheritDoc */
    #[Override]
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
            $buffer = match ($node::class) {
                Variable::class => $this->handleVariableNode($node, $context),
                FnCall::class => $this->handleFunctionCall($buffer, $node, $context),
                Filter::class => $this->handleFilter($buffer, $node, $context),
                default => $buffer,
            };
        }

        if (is_bool($buffer) || $buffer === null) {
            return '';
        }

        if (is_scalar($buffer) || $buffer instanceof Stringable) {
            return (string) $buffer;
        }

        return '';
    }

    /** @throws RenderingFailed */
    private function handleVariableNode(Variable $node, Context $context): mixed
    {
        try {
            return $context->extract($node->name);
        } catch (VariableNotFound $error) {
            throw RenderingFailed::becauseOfAMissingVariable($error);
        }
    }

    /** @throws RenderingFailed */
    private function handleFunctionCall(mixed $buffer, FnCall $node, Context $context): mixed
    {
        try {
            return $this->callFunction($node, $context);
        } catch (FunctionNotFound $error) {
            if ($this->options->skipMissingFunctions) {
                return $buffer;
            }

            throw RenderingFailed::becauseAFunctionCouldNotBeFound($error);
        } catch (VariableNotFound $error) {
            throw RenderingFailed::becauseOfAMissingVariable($error);
        } catch (Throwable $error) {
            throw RenderingFailed::becauseOfAnUnknownError($error);
        }
    }

    /** @throws RenderingFailed */
    private function handleFilter(mixed $buffer, Filter $node, Context $context): mixed
    {
        try {
            return $this->callFilter($node, $buffer, $context);
        } catch (FilterNotFound $error) {
            if ($this->options->skipMissingFilters) {
                return $buffer;
            }

            throw RenderingFailed::becauseAFilterCouldNotBeFound($error);
        } catch (VariableNotFound $error) {
            throw RenderingFailed::becauseOfAMissingVariable($error);
        } catch (Throwable $error) {
            throw RenderingFailed::becauseOfAnUnknownError($error);
        }
    }

    /**
     * @throws FunctionNotFound
     * @throws VariableNotFound
     */
    private function callFunction(FnCall $node, Context $context): mixed
    {
        $args = map(
            $node->arguments,
            /** @throws VariableNotFound */
            static fn (Variable $variable): mixed => $context->extract($variable->name),
        );

        $function = $this->functions->get($node->name);

        return $function(...$args);
    }

    /**
     * @throws FilterNotFound
     * @throws VariableNotFound
     */
    private function callFilter(Filter $node, mixed $buffer, Context $context): mixed
    {
        $filter = $this->filters->get($node->name);

        return $filter($buffer, $context);
    }
}
