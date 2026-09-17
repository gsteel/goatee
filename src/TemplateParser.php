<?php

declare(strict_types=1);

namespace GSteel\Goatee;

use GSteel\Goatee\Expression\Node\Node;

interface TemplateParser
{
    /**
     * Parse a template into a list of Nodes or throw an exception
     *
     * @return list<Node>
     * @throws SourceError
     */
    public function parseTemplate(string $template): array;
}
