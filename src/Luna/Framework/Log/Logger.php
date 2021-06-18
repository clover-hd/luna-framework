<?php

namespace Luna\Framework\Log;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;
use Luna\Framework\Http\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * ログ出力クラス
 */
class Logger implements LoggerInterface
{
    /**
     * アプリケーションインスタンス
     *
     * @var Application
     */
    protected $application;
    /**
     * リクエストインスタンス
     *
     * @var Request
     */
    protected $request;
    /**
     * ログレベル
     *
     * @var string
     */
    protected $logLevel;
    /**
     * ログファイル
     *
     * @var string
     */
    protected $logfile;
    /**
     * ログ出力先(file,stdout,php)
     *
     * @var string
     */
    protected $output;

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
        if (isset($configParams['system']['log']['output'])) {
            $this->output = $configParams['system']['log']['output'];
        } else {
            $this->output = 'file';
        }
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
        $outputLevel = $this->getLogLevelNo($level);
        $confLevel = $this->getLogLevelNo($this->logLevel);
        if ($outputLevel >= $confLevel) {
            $text = $this->datetimeString() . "\t" . $this->request->getClientIPAddress() . "\t" . strtoupper($level) . "\t" . $this->interpolate($message, $context);
            if ($this->output == 'file') {
                file_put_contents($this->logfile, $text . PHP_EOL, FILE_APPEND | LOCK_EX);
            } else if ($this->output == 'stdout') {
                $fp = fopen('php://stdout', 'wb');
                fputs($fp, $text . "\n");
                fclose($fp);
            } else if ($this->output == 'php') {
                error_log($text . "\n", 0);
            }
        }
    }

    private function getLogLevelNo($level)
    {
        $levelNo = 0;

        switch ($level)
        {
            case LogLevel::DEBUG:
                $levelNo = 0;
                break;
            case LogLevel::INFO:
                $levelNo = 1;
                break;
            case LogLevel::NOTICE:
                $levelNo = 2;
                break;
            case LogLevel::WARNING:
                $levelNo = 3;
                break;
            case LogLevel::ERROR:
                $levelNo = 4;
                break;
            case LogLevel::CRITICAL:
                $levelNo = 5;
                break;
            case LogLevel::ALERT:
                $levelNo = 6;
                break;
            case LogLevel::EMERGENCY:
                $levelNo = 7;
                break;
        }

        return $levelNo;
    }
}