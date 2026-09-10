<?php

declare(strict_types=1);

namespace GSteel\Goatee\Expression;

enum TokenType
{
    case Symbol;
    case Pipe;
    case LParen;
    case RParen;
}
