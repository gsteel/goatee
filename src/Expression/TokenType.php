<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Expression;

enum TokenType
{
    case Symbol;
    case Pipe;
    case LParen;
    case RParen;
}
