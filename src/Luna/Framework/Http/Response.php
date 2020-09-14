<?php

namespace Luna\Framework\Http;

use Luna\Framework\Application\Application;
use Luna\Framework\Controller\Controller;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;
use Luna\Framework\View\View;

class Response
{
    protected $status = 200;
    protected $headers = array();
    protected $view;

    public function init(Routes $routes, Route $route)
    {
        if ($this->view != null)
        {
            $this->view->init($routes, $route);
        }
    }

    public function outputHeader()
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $val)
        {
            header($name . ":" . $val);
        }
    }

    public function status(int $status)
    {
        $this->status = $status;
        return $this;
    }

    public function header(string $name, string $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function view(View $view)
    {
        $this->view = $view;
        return $this;
    }

    public function render()
    {
        if ($this->view != null)
        {
            $this->view->render();
        }
    }
}