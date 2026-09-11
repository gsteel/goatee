<?php

declare(strict_types=1);

namespace GSteel\Goatee;

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
        public bool $strictVariables = false,
    ) {}

    /** @throws VariableNotFound */
    public function extract(string $name): mixed
    {
        $parts = explode('.', $name);
        $model = $this->model;

        foreach ($parts as $part) {
            if (is_array($model) || is_object($model)) {
                /** @var mixed $model */
                $model = $this->extractFrom($part, $model, $name);

                continue;
            }

            if (! $this->strictVariables) {
                return null;
            }

            throw VariableNotFound::atPosition($name, $part);
        }

        return $model;
    }

    /**
     * @param object|array<array-key, mixed> $model
     * @throws VariableNotFound
     */
    private function extractFrom(string $name, object|array $model, string $requestedName): mixed
    {
        $model = is_object($model) ? get_object_vars($model) : $model;

        if (array_key_exists($name, $model)) {
            return $model[$name];
        }

        if (! $this->strictVariables) {
            return null;
        }

        throw $name === $requestedName
            ? VariableNotFound::byName($requestedName)
            : VariableNotFound::atPosition($requestedName, $name);
    }
}
