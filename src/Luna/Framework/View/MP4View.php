<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class MP4View extends View
{
    protected $srcFilepath;
    protected $stream;

    public function __construct(Application $application, Request $request, $stream)
    {
        parent::__construct($application, $request);
        $this->stream = $stream;
    }

    public function init(Routes $routes, Route $route)
    {
    }

    public function render()
    {
        fpassthru($this->stream);
    }

    public function fetch()
    {
    }

}
