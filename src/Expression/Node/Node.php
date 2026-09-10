<?php

declare(strict_types=1);

namespace GSteel\Goatee\Expression\Node;

interface Node
{
    public string $name { get; }
    /** @var non-negative-int */
    public int $pos { get; }
    /** @var positive-int */
    public int $line { get; }
}
