<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\JsonView;

trait RenderJson
{

    public function renderJson(array $params): Response
    {
        return (new Response())
            ->header('Content-type', 'application/json; charset=utf-8')
            ->view(new JsonView(
                $this->application,
                $this->request,
                $params
            )
            );
    }

}
