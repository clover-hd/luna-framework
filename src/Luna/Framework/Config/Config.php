<?php

namespace Luna\Framework\Config;

use Symfony\Component\Yaml\Yaml;

use Luna\Framework\Application\Application;

class Config
{
    /**
     * インスタンス
     *
     * @var Config
     */
    private static $instance;
    /**
     * 設定ファイルディレクトリ
     *
     * @var string
     */
    private $configPath = '/';

    /**
     * コンフィグ設定値
     *
     * @var array
     */
    private $config = array();
    /**
     * ルート設定値
     *
     * @var array
     */
    private $routes = array();
    /**
     * CLI設定値
     *
     * @var array
     */
    private $console = array();
    /**
     * アプリケーション設定値
     *
     * @var array
     */
    private $application = array();
    /**
     * URLリライト設定値
     *
     * @var array
     */
    private $urlrewrite = array();

    /**
     * シングルトンインスタンスを返す
     *
     * @return Config
     */
    public function getInstance()
    {
        if (Config::$instance == null) {
            Config::$instance = new Config();
            Config::$instance->loadConfig();
        }

        return Config::$instance;
    }

    /**
     * コンストラクタ
     *
     * @param string $configPath 設定ファイルディレクトリ
     */
    public function __construct($configPath = 'config/') {
        $this->configPath = $configPath;
        $this->loadConfig();
    }

    /**
     * 設定値をファイルから読み込む
     *
     * @return void
     */
    public function loadConfig()
    {
        if (file_exists($this->configPath . 'config.yml')) {
            $this->config = Yaml::parseFile($this->configPath . 'config.yml', Yaml::PARSE_CONSTANT);
        }
        if (file_exists($this->configPath . 'routes.yml')) {
            $this->routes = Yaml::parseFile($this->configPath . 'routes.yml', Yaml::PARSE_CONSTANT);
        }
        if (file_exists($this->configPath . 'console.yml')) {
            $this->console = Yaml::parseFile($this->configPath . 'console.yml', Yaml::PARSE_CONSTANT);
        }
        if (file_exists($this->configPath . 'application.yml')) {
            $this->application = Yaml::parseFile($this->configPath . 'application.yml', Yaml::PARSE_CONSTANT);
        }
        if (file_exists($this->configPath . 'urlrewrite.yml')) {
            $this->urlrewrite = Yaml::parseFile($this->configPath . 'urlrewrite.yml', Yaml::PARSE_CONSTANT);
        }
    }

    /**
     * コンフィグ設定値を返す
     *
     * @return array
     */
    public function getConfigParams()
    {
        return $this->config;
    }

    /**
     * ルート設定値を返す
     *
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routes;
    }

    /**
     * CLI設定値を返す
     *
     * @return array
     */
    public function getConsoleParams()
    {
        return $this->console;
    }

    /**
     * アプリケーション設定値を返す
     *
     * @return array
     */
    public function getApplicationParams()
    {
        return $this->application;
    }

    /**
     * URLリライト設定値を返す
     *
     * @return array
     */
    public function getUrlRewriteParams()
    {
        return $this->urlrewrite;
    }

    /**
     * 環境設定値を返す
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->config['system']['environment'];
    }

    /**
     * システム設定パラメータを返す
     *
     * @return array
     */
    public function getSystemParams()
    {
        return $this->config['system'];
    }

    /**
     * minify設定パラメータを返す
     *
     * @return array
     */
    public function getMinifyParams()
    {
        $environment = $this->config['system']['environment'];
        return $this->config['minify'][$environment];
    }

    /**
     * SMTP設定パラメータを返す
     *
     * @return array
     */
    public function getSmtpParams()
    {
        $environment = $this->config['system']['environment'];
        return $this->config['smtp'][$environment];
    }

    /**
     * データソース設定パラメータを返す
     *
     * @return array
     */
    public function getDatasourceParams()
    {
        $environment = $this->config['system']['environment'];
        return $this->config['datasource'][$environment];
    }
}
