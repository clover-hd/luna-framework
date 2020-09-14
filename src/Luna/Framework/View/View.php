<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Controller\Controller;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

abstract class View
{
    protected $application;
    protected $request;

    public function __construct(Application $application, Request $request)
    {
        $this->application = $application;
        $this->request = $request;
    }
    abstract public function init(Routes $routes, Route $route);
    abstract public function render();
}
