<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class ImageView extends View
{
    protected $filepath;

    public function __construct(Application $application, Request $request, string $filepath)
    {
        parent::__construct($application, $request);
        $this->filepath = $filepath;
    }

    public function init(Routes $routes, Route $route)
    {
    }

    public function render()
    {
        readfile($this->filepath);
    }

    public function fetch()
    {
        return file_get_contents($this->filepath);
    }

}
