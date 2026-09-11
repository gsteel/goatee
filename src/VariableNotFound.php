<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use RuntimeException;

use function sprintf;

final class VariableNotFound extends RuntimeException
{
    public static function byName(string $name): self
    {
        return new self(
            sprintf(
                'A variable with the name "%s" cannot be found in the data model',
                $name,
            ),
        );
    }

    public static function atPosition(string $requestedName, string $position): self
    {
        return new self(
            sprintf(
                'The variable "%s" could not be found at position "%s"',
                $requestedName,
                $position,
            ),
        );
    }
}
