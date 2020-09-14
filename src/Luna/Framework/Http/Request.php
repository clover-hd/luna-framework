<?php

namespace Luna\Framework\Http;

use Luna\Framework\Application\Application;
use Luna\Framework\Controller\InvalidCSRFTokenException;
use Luna\Framework\Http\Cookie\Cookie;
use Luna\Framework\Http\Session\Session;

/**
 * 
 * @method GetVars getGet()
 * @method PostVars getPost()
 * @method RequestVars getRequest()
 */
class Request
{
    public const GET = 'get';
    public const HEAD = 'head';
    public const POST = 'post';
    public const PUT = 'put';
    public const DELETE = 'delete';
    public const CONNECT = 'connect';
    public const OPTIONS = 'options';
    public const TRACE = 'trace';
    public const PATCH = 'patch';

    protected $application;
    protected $session;
    protected $cookie;
    protected $getVars;
    protected $postVars;
    protected $headers;
    protected $routeParams;
    protected $argv;
    protected $argc;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->init();
    }

    public function init()
    {
        global $argv, $argc;

        $config = $this->application->getConfig()->getConfigParams();
        // セッションクラスの初期化
        // ※セッションの設定がなければセッションを開始しない
        if (isset($config['session'])) {
            // クラス指定があれば、指定のクラスで初期化
            if ($config['session']['class']) {
                $className = $config['session']['class'];
            } else {
                // クラス指定がなければデフォルトのセッションクラスで初期化
                $className = '\Luna\Framework\Http\Session\Session';
            }
            $this->session = new $className($this->application, $this);
            if (session_status() !== PHP_SESSION_ACTIVE) {
                $this->session->start();
            }
        }

        // クッキー
        $this->cookie = new Cookie();
        // HTTP変数
        $this->getVars = new GetVars();
        $this->postVars = new PostVars();
        $this->requestVars = new RequestVars();

        // CLIで実行しているか
        if (PHP_SAPI === 'cli') {
            // CLI実行時のコマンドライン引数
            $this->argv = $argv;
            $this->argc = $argc;
        } else {
        // Request Header
            $this->headers = apache_request_headers();
        }
    }

    public function getApplication()
    {
        return $this->getApplication();
    }

    public function getProtocol()
    {
        return $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    }

    public function getRequestMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getDomain()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }

    public function getClientIPAddress()
    {
        $remoteAddr = '';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $remoteAddr = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            list($remoteAddr) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        return $remoteAddr;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getCookie()
    {
        return $this->cookie;
    }

    public function getGet()
    {
        return $this->getVars;
    }

    public function getPost()
    {
        return $this->postVars;
    }

    public function getRequest()
    {
        return $this->requestVars;
    }

    public function getArgv()
    {
        return $this->argv;
    }

    public function getArgc()
    {
        return $this->argc;
    }

    public function get(string $name, string $default = null)
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        } else {
            return $default;
        }
    }

    public function post(string $name, string $default = null)
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        } else {
            return $default;
        }
    }

    public function request(string $name, string $default = null)
    {
        if (isset($_REQUEST[$name])) {
            return $_POST[$name];
        } else {
            return $default;
        }
    }

    /**
     * アップロードファイルを返す。同名のパラメータ名で複数アップロードがあった場合は一番目のファイルのみ返す。
     *
     * @param string $name
     * @return UploadFile|null
     */
    public function file(string $name): ?UploadFile
    {
        if (isset($_FILES[$name])) {
            if (\is_array($_FILES[$name]['name']) === false) {
                return new UploadFile($_FILES[$name]);
            } else {
                return new UploadFile([
                    'name' => $_FILES[$name]['name'][0],
                    'type' => $_FILES[$name]['type'][0],
                    'tmp_name' => $_FILES[$name]['tmp_name'][0],
                    'error' => $_FILES[$name]['error'][0],
                    'size' => $_FILES[$name]['size'][0],
                ]);
            }
        } else {
            return null;
        }
    }

    /**
     * アップロードファイルを取得する。同名のパラメータ名で複数アップロード対応。
     *
     * @param string $name
     * @return Luna\Framework\Http\UploadFile[]|null
     */
    public function files(string $name): ?array
    {
        if (isset($_FILES[$name])) {
            if (\is_array($_FILES[$name]['name']) === false) {
                return [new UploadFile($_FILES[$name])];
            } else {
                $uploadFiles = [];
                for ($i = 0; $i < count($_FILES[$name]['name']); $i++) {
                    $file = [
                        'name' => $_FILES[$name]['name'][$i],
                        'type' => $_FILES[$name]['type'][$i],
                        'tmp_name' => $_FILES[$name]['tmp_name'][$i],
                        'error' => $_FILES[$name]['error'][$i],
                        'size' => $_FILES[$name]['size'][$i],
                    ];
                    $uploadFiles[] = new UploadFile($file);
                }
                return $uploadFiles;
            }
        } else {
            return null;
        }
    }

    public function header(string $name, string $default = null)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        } else {
            return $default;
        }
    }

    public function setRouteParams(array $routeParams)
    {
        $this->routeParams = $routeParams;
    }

    public function getRouteparams()
    {
        return $this->routeParams;
    }

    public function routeParam(string $name, string $default = null)
    {
        if (isset($this->routeParams[$name])) {
            return $this->routeParams[$name];
        } else {
            return $default;
        }
    }

    public function getRequestUrl()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function generateCSRFToken(): string
    {
        $csrfToken = bin2hex(random_bytes(32));
        $this->getSession()->put('_csrf_token', $csrfToken);

        return $csrfToken;
    }

    public function validateCSRFToken()
    {
        if ($this->header('X-CSRF-TOKEN', '') != '') {
            if ($this->header('X-CSRF-TOKEN', '') != $this->getSession()->get('_csrf_token')) {
                throw new InvalidCSRFTokenException();
            }
        } else {
            if ($this->post('_csrf_token', '') != $this->getSession()->get('_csrf_token')) {
                throw new InvalidCSRFTokenException();
            }
        }
    }
}
