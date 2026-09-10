<?php

declare(strict_types=1);

namespace GSteel\TemplateString;

use function array_key_exists;
use function explode;
use function get_object_vars;
use function is_array;
use function is_object;

final readonly class Context
{
    /** @param object|array<array-key, mixed> $model */
    public function __construct(
        public object|array $model,
    ) {
    }

    public function extract(string $name): mixed
    {
        $parts = explode('.', $name);
        $model = $this->model;

        foreach ($parts as $part) {
            if (! is_array($model) && ! is_object($model)) {
                return null;
            }

            /** @var mixed $model */
            $model = $this->extractFrom($part, $model);
        }

        return $model;
    }

    /** @param object|array<array-key, mixed> $model */
    private function extractFrom(string $name, object|array $model): mixed
    {
        $model = is_object($model) ? get_object_vars($model) : $model;

        if (array_key_exists($name, $model)) {
            return $model[$name];
        }

        return null;
    }
}
