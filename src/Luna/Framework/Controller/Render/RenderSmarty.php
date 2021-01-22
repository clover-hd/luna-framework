<?php

namespace Luna\Framework\Controller\Render;

use Luna\Framework\Http\Response;
use Luna\Framework\View\SmartyView;
use Luna\Framework\View\SmartySjisView;

trait RenderSmarty
{

    public function render(string $template, array $params, string $encode=''): Response
    {
        if ($encode == 'sjis') {
            return (new Response())
            ->view(new SmartySjisView(
                $this->application,
                $this->request,
                $template,
                $params
            )
            );
        } else {
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

}
