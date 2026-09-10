<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use RuntimeException;

use function sprintf;

final class FilterNotFound extends RuntimeException
{
    public static function byName(string $name): self
    {
        return new self(sprintf(
            'A filter could not be found by the name "%s"',
            $name,
        ));
    }
}
