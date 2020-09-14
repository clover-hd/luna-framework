<?php

namespace Luna\Framework\Http\Session;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;

/**
 * セッションを管理するクラス。<br />
 * PHP標準のセッション管理を行う。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package session
 */
class Session
{
    protected $application;
    protected $request;

    /**
     * コンストラクタ
     *
     * @param array $session セッションデータ
     */
    public function __construct(Application $application, Request $request)
    {
        $this->application = $application;
        $this->request = $request;
        $this->init();
    }

    /**
     * セッションの初期設定
     */
    protected function init()
    {
        $config = $this->application->getConfig()->getConfigParams();
        // config.ymlにセッション設定があれば設定を行う
        if (isset($config['session'])) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                // セッション名
                if (isset($config['session']['session_name'])) {
                    session_name($config['session']['session_name']);
                }
                // セッションライフタイム
                if (isset($config['session']['session_lifetime'])) {
                    session_set_cookie_params(
                        $config['session']['session_lifetime'],
                        $this->application->getRootPath(),
                        $this->request->getDomain(),
                        $this->request->getProtocol() == 'https'
                    );
                } else {
                    session_set_cookie_params(
                        0,
                        $this->application->getRootPath(),
                        $this->request->getDomain(),
                        $this->request->getProtocol() == 'https'
                    );
                }
                // セッションセーブパス
                if (isset($config['session']['save_path'])) {
                    if (substr($config['session']['save_path'], 0, 1) == '/') {
                        session_save_path($config['session']['save_path']);
                    } else {
                        session_save_path($this->application->getProjectPath() . '/' . $config['session']['save_path']);
                    }
                }
            }
        }
    }

    /**
     * セッションを開始します。
     */
    public function start()
    {
        session_start();
    }

    /**
     * SIDを返します。
     *
     * @return    string    $sid    SID
     */
    public function getSessionID()
    {
        return session_id();
    }

    /**
     * SIDをセットします。<br />
     *
     */
    public function setSessionID(string $sid)
    {
        session_id($sid);
    }

    /**
     * セッション名を返します。
     *
     * @return    string    session_name()
     */
    public function getSessionName()
    {
        return session_name();
    }

    /**
     * セッションIDを再生成する
     *
     * @param boolean $deleteOldSession
     * @return void
     */
    public function regenerateId(bool $deleteOldSession = false)
    {
        session_regenerate_id($deleteOldSession);
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __put(string $name, $value)
    {
        $this->put($name, $value);
    }

    public function get(string $name, $default = null)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return $default;
        }
    }

    public function put(string $name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function remove(string $name)
    {
        unset($_SESSION[$name]);
    }

    public function getSessionVars()
    {
        return $_SESSION;
    }
}
