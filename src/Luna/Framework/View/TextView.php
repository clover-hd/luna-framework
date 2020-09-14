<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class TextView extends View
{
    protected $text;

    public function __construct(Application $application, Request $request, string $text, string $charset = 'utf-8')
    {
        parent::__construct($application, $request);
        $this->text = $text;
        $this->charset = $charset;
    }

    public function init(Routes $routes, Route $route)
    {
    }

    public function render()
    {
        echo mb_convert_encoding($this->text, $this->charset);
    }

    public function fetch()
    {
        return mb_convert_encoding($this->text, $this->charset);
    }

}
