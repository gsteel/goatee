<?php

declare(strict_types=1);

namespace GSteel\Goatee;

interface Renderer
{
    /**
     * Render a template string with the given model
     *
     * @param array<array-key, mixed>|object $model
     * @return ($template is non-empty-string ? non-empty-string : string)
     * @throws RenderingFailed
     */
    public function render(string $template, array|object $model = []): string;
}
