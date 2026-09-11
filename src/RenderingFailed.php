<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use RuntimeException;
use Throwable;

use function sprintf;

final class RenderingFailed extends RuntimeException
{
    public static function becauseOfAnInvalidTemplate(SourceError $error): self
    {
        return new self(
            sprintf(
                'Template rendering failed because the template was not valid: %s',
                $error->getMessage(),
            ),
            0,
            $error,
        );
    }

    public static function becauseAFilterCouldNotBeFound(FilterNotFound $error): self
    {
        return new self(
            $error->getMessage(),
            0,
            $error,
        );
    }

    public static function becauseAFunctionCouldNotBeFound(FunctionNotFound $error): self
    {
        return new self(
            $error->getMessage(),
            0,
            $error,
        );
    }

    public static function becauseOfAnUnknownError(Throwable $error): self
    {
        return new self(
            sprintf('An exception occurred during rendering: %s', $error->getMessage()),
            0,
            $error,
        );
    }

    public static function becauseOfAMissingVariable(VariableNotFound $error): self
    {
        return new self(
            $error->getMessage(),
            0,
            $error,
        );
    }
}
