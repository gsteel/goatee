<?php

declare(strict_types=1);

namespace GSteel\TemplateString;

use function array_key_exists;

final class FunctionManager
{
    /** @var array<string, callable(mixed...): mixed> */
    private array $functions;

    /** @param iterable<string, callable(mixed...): mixed> $functions */
    public function __construct(iterable $functions = [])
    {
        foreach ($functions as $name => $function) {
            $this->registerFunction($name, $function);
        }
    }

    /** @param callable(mixed...): mixed $function */
    public function registerFunction(string $name, callable $function): void
    {
        $this->functions[$name] = $function;
    }

    /**
     * @return callable(mixed...): mixed
     * @throws FunctionNotFound
     */
    public function get(string $name): callable
    {
        $function = $this->functions[$name] ?? null;
        if ($function === null) {
            throw FunctionNotFound::byName($name);
        }

        return $function;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->functions);
    }
}
