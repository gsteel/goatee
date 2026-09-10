<?php

declare(strict_types=1);

namespace GSteel\TemplateString\Template;

enum TokenType
{
    case String;
    case Expression;
}
