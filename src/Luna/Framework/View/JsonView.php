<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Controller\Controller;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;

class JsonView extends View
{
    protected $controller;
    protected $params;
    protected $jsonParams;

    public function __construct(Application $application, Request $request, array $params)
    {
        parent::__construct($application, $request);
        $this->params = $params;
    }

    public function init(Routes $routes, Route $route)
    {
        $this->jsonParams = array();

        // // システム変数
        // $this->jsonParams['ROOT_URL'] = $routes->getRootUrl();

        // // HTTP変数をアサイン
        // $this->jsonParams['get'] = $request->getGet();
        // $this->jsonParams['post'] = $request->getGet();
        // $this->jsonParams['request'] = $request->getRequest();
        // $this->jsonParams['session'] = $request->getSession()->getSessionVars();
        // $this->jsonParams['cookie'] = $request->getCookie()->getCookieVars();
        // $this->jsonParams['routeParams'] = $request->getRouteparams();

        // パラメータをアサイン
        foreach ($this->params as $name => $val) {
            $this->jsonParams[$name] = $val;
        }
    }

    public function render()
    {
        echo json_encode($this->jsonParams);
    }

    public function fetch()
    {
        return json_encode($this->jsonParams);
    }
}
