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
class CLIDispatcher
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
    public function dispatch(array $argv)
    {

        $request = new Request($this->application);
        $consoleParams = $this->application->getConfig()->getConsoleParams();
        $commandName = $argv[1];
        $route = $consoleParams['console'][$commandName];
        if (is_array($route) === false) {
            echo "Not found {$argv[1]}\n";
            exit;
        }
        $actionClassName = $route['class'];
        $actionMethodName = $route['method'];

        $controller = new $actionClassName;
        $controller->setApplication($this->application);

        try {

            try {
                // ロガー
                $logger = new Logger();
                $logger->setFilename($commandName);
                $controller->setLogger($logger);
                // コントローラクラス初期化処理
                $result = $controller->init($request);

                $response = $controller->$actionMethodName($request);
            } catch (Error $ex) {
                $response = $controller->catchException($request, $ex);
            } catch (Exception $ex) {
                $response = $controller->catchException($request, $ex);
            }

        } catch (Exception $ex) {
            echo $ex->getMessage() . "\n";
        }
    }

}
