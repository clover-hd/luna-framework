<?php

namespace Luna\Framework\Log;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Http\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PHP設定に準じたログ出力クラス
 */
class PhpLogger implements LoggerInterface
{
    /**
     * アプリケーションインスタンス
     *
     * @var Application
     */
    private $application;
    /**
     * リクエストインスタンス
     *
     * @var Request
     */
    private $request;
    /**
     * ログレベル
     *
     * @var string
     */
    private $logLevel;
    /**
     * ログファイル
     *
     * @var string
     */
    private $logfile;

    /**
     * コンストラクタ
     *
     * @param Response $request
     */
    public function __construct(Response $request = null)
    {
        $this->application = Application::getInstance();
        $this->request = new Request($this->application);
        $configParams = $this->application->getConfig()->getConfigParams();
        $this->logLevel = $configParams['system']['log']['loglevel'];
        $projectPath = Application::getInstance()->getProjectPath();
        $this->logfile = $projectPath . '/log/application-' . date('Ymd') . '.log';
    }

    /**
     * ログファイル名を設定する
     * 設定したファイル名は"{ファイル名}-{Ymd}.log"となる
     *
     * @param string $filename
     * @return void
     */
    public function setFilename($filename)
    {
        $projectPath = Application::getInstance()->getProjectPath();
        $this->logfile = $projectPath . "/log/{$filename}-" . date('Ymd') . '.log';
    }

    protected function interpolate($message, array $context = array())
    {
        // コンテクストキーの周りに中カッコを入れた置換配列を作成する
        $replace = array();
        foreach ($context as $key => $val) {
            // 値を文字列にキャストできることを確認する
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
    
        // 置換値をメッセージに補間して戻す
        return strtr($message, $replace);
    }

    /**
     * 日時を文字列で返す
     *
     * @return string
     */
    protected function datetimeString()
    {
        return date("Y-m-d H:i:s") . "." . substr(explode(".", (microtime(true) . ""))[1], 0, 3);
    }

    /**
     * emergencyログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * alertログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * criticalログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * errorログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * warningログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * noticeログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * infoログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * debugログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * ログを出力する
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $text = $this->datetimeString() . "\t" . $this->request->getClientIPAddress() . "\t" . strtoupper($level) . "\t" . $this->interpolate($message, $context);
        error_log($message, 0);
    }
}