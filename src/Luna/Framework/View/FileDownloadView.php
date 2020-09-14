<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class FileDownloadView extends View
{
    protected $srcFilepath;
    protected $filename;

    public function __construct(Application $application, Request $request, string $srcFilepath, string $filename)
    {
        parent::__construct($application, $request);
        $this->srcFilepath = $srcFilepath;
        $this->filename = $filename;
    }

    public function init(Routes $routes, Route $route)
    {
    }

    public function render()
    {
        readfile($this->srcFilepath);
    }

    public function fetch()
    {
        return file_get_contents($this->srcFilepath);
    }

}
