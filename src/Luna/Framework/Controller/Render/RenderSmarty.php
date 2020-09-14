<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\SmartyView;

trait RenderSmarty
{

    public function render(string $template, array $params): Response
    {
        return (new Response())
            ->view(new SmartyView(
                $this->application,
                $this->request,
                $template,
                $params
            )
            );
    }

}
