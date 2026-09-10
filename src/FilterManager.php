<?php

declare(strict_types=1);

namespace GSteel\TemplateString;

use function array_key_exists;

final class FilterManager
{
    /** @var array<string, callable(mixed, Context): mixed> */
    private array $filters;

    /** @param iterable<string, callable(mixed, Context): mixed> $filters */
    public function __construct(iterable $filters = [])
    {
        foreach ($filters as $name => $filter) {
            $this->registerFilter($name, $filter);
        }
    }

    /** @param callable(mixed, Context): mixed $filter */
    public function registerFilter(string $name, callable $filter): void
    {
        $this->filters[$name] = $filter;
    }

    /**
     * @return callable(mixed, Context): mixed
     * @throws FilterNotFound
     */
    public function get(string $name): callable
    {
        $filter = $this->filters[$name] ?? null;
        if ($filter === null) {
            throw FilterNotFound::byName($name);
        }

        return $filter;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->filters);
    }
}
