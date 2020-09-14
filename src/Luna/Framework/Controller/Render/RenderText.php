<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\TextView;

trait RenderText
{

    public function renderText(string $text, $charset = 'utf-8'): Response
    {
        return (new Response())
            ->header('Content-type', 'text/plain; charset=' . $charset)
            ->view(
                (new TextView(
                    $this->application,
                    $this->request,
                    $text,
                    $charset
                )
                )
            );
    }
}
