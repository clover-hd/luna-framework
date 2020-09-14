<?php

namespace Luna\Framework\Controller;

use Luna\Framework\Application\Application;
use Luna\Framework\Database\DataSource;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;
use Psr\Log\LoggerAwareTrait;

class Controller
{
    use LoggerAwareTrait;

    protected $application;
    protected $request;
    protected $routes;
    protected $route;

    /**
     * 初期設定メソッド
     *
     * @param Request $request
     * @return static
     */
    public function init(Request $request)
    {
        return $this;
    }

    public function catchException(Request $request, $ex)
    {
        throw $ex;
    }

    /**
     * Applicationをセットする
     *
     * @param Application $application
     * @return static
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * Applicationを返す
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Requestをセットする
     *
     * @param Request $request
     * @return static
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Requestを返す
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Routesをセットする
     *
     * @param Routes $routes
     * @return void
     */
    public function setRoutes(Routes $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Routesを返す
     *
     * @return Routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Routeをセットする
     *
     * @param Route $route
     * @return static
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Routeを返す
     *
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    // /**
    //  * ロケースファイルに設定されているメッセージを返す
    //  *
    //  * @param array $keys
    //  * @return string
    //  */
    // public function getMessage(array $keys): string
    // {
    //     $config = $this->application->getLocaleConfig();
    //     foreach ($keys as $key) {
    //         if (isset($config[$key])) {
    //             $config = $config[$key];
    //         } else {
    //             return implode('.', $keys);
    //         }
    //     }
    //     return $config;
    // }

    /**
     * トランザクションを開始する
     *
     * @param string $datasourceName トランザクションを開始するデータソース名
     * @return static
     */
    protected function beginTransaction(string $datasourceName = 'default')
    {
        DataSource::getDataSource($datasourceName)
            ->beginTransaction();

        return $this;
    }

    /**
     * トランザクションをcommitする
     *
     * @param string $datasourceName トランザクションをcommitするデータソース名
     * @return static
     */
    protected function commit(string $datasourceName = 'default')
    {
        DataSource::getDataSource($datasourceName)
            ->commit();
    }

    /**
     * トランザクションをrollbackする
     *
     * @param string $datasourceName トランザクションをrollbackするデータソース名
     * @return static
     */
    protected function rollback(string $datasourceName = 'default')
    {
        DataSource::getDataSource($datasourceName)
            ->rollback();
    }

    /**
     * 指定したルートパスのFDQNを返す
     *
     * @param Request $request
     * @param string $routePath
     * @param string $method
     * @return string
     */
    protected function getRouteUrl(Request $request, string $routePath, string $method = 'get')
    {
        return $this->getRoutes()->getRouteByUri($request, $routePath, $method)->getUrl();
    }
}
