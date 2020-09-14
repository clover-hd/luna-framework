<?php

namespace Luna\Framework\Dispatcher;

use Error;
use Exception;
use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Log\Logger;
use Luna\Framework\Routes\Routes;
use Luna\Framework\Session\Session;

/**
 * 標準のディスパッチャークラス。<br />
 * このクラスで各モジュールが呼び出されます。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package filter
 */
class Dispatcher
{
    protected $application;

    protected $moduleName = '';

    protected $packageName = '';

    /* @var action class */
    protected $actionClassName = '';
    protected $actionClassPackageName = '';
    protected $actionClassMethod = "";

    protected $contextXML;

    protected $request = array();

    protected $error = array();

    protected $actionParams = array();

    /**
     * コンストラクタ
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * context.xmlで指定されているセッションクラスを作成し返す。
     *
     * @return LunaSession セッションクラスのインスタンス
     */
    public function getSession()
    {
        $config = $this->application->getConfig();
        $className = $config->getConfigParams()['session']['class'];

        $sessionClass = new $className($this->application);

        $session = $sessionClass->getInstance();

        return $session;
    }

    /**
     * アプリケーションのクラスをロード、作成し実行する。
     *
     * @param string $path パス
     */
    public function dispatch(string $uri)
    {
        $request = new Request($this->application);
        $routes = new Routes($this->application);
        $route = $routes->getRouteByUri($request, $uri);
        if ($route == null) {
            // TODO: 404
            header("HTTP/1.0 404 Not Found");
            header("Content-Type: text/html; charset=us-ascii");
            exit;
        }

        // アクションクラス生成
        $actionClassName = $route->getClassName();
        $actionMethodName = $route->getActionMethodName();
        $controller = new $actionClassName;

        $controller->setApplication($this->application);
        $controller->setRequest($request);
        $controller->setRoutes($routes);
        $controller->setRoute($route);

        try {

            try {
                // ロガー
                $controller->setLogger(new Logger());
                // コントローラクラス初期化処理
                $result = $controller->init($request);

                $response = $controller->$actionMethodName($request);
                $response->init($routes, $route);
                $response->outputHeader();
                $response->render();
            } catch (Error $ex) {
                $response = $controller->catchException($request, $ex);
                $response->init($routes, $route);
                $response->outputHeader();
                $response->render();
            } catch (Exception $ex) {
                $response = $controller->catchException($request, $ex);
                $response->init($routes, $route);
                $response->outputHeader();
                $response->render();
            }

        } catch (Exception $ex) {
            $response = $controller->catchException($request, $ex);
            $response->init($routes, $route);
            $response->outputHeader();
            $response->render();
        }
    }

}
