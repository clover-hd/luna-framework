<?php

namespace Luna\Framework\Application;

use Locale;
use Luna\Framework\Config\Config;
use Symfony\Component\Yaml\Yaml;

class Application
{
    /**
     * アプリケーションオブジェクト
     */
    private static $application;

    /**
     * アプリケーション設定
     */
    private $config;

    /**
     * ロケールごとの設定
     *
     * @var [type]
     */
    private $localeConfig;

    /**
     * プロジェクトのパス
     */
    private $bootparams;

    /**
     * アクションパラメータ
     */
    private $actionParams;

    private function __construct(array $bootparams)
    {
        $this->bootparams = $bootparams;
        $this->loadConfig();
    }

    public static function getInstance(): Application
    {
        global $bootparams;

        if (Application::$application == null)
        {
            Application::$application = new Application($bootparams);
        }

        return Application::$application;
    }

    public function loadConfig()
    {
        $this->config = new Config($this->bootparams['projectPath'] . '/config/');
        $this->config->loadConfig();
    }

    public function getLocaleConfig(string $locale = null): array
    {
        if (is_null($locale))
        {
            $configParams = $this->config->getConfigParams();
            if (isset($configParams['system']['defaultLocale'])) {
                $locale = $configParams['system']['defaultLocale'];
            } else {
                $locale = Locale::getDefault();
            }
        }
        // 既に読み込み済みであればそのまま返す
        if (isset($this->localeConfig[$locale]))
        {
            return $this->localeConfig[$locale];
        }

        // まだ読み込まれていない場合はファイルから読み込む
        $path = $this->bootparams['projectPath'] . '/config/locale/' . $locale . '.yml';
        if (file_exists($path))
        {
            $this->localeConfig[$locale] = Yaml::parseFile($path);
            return $this->localeConfig[$locale];
        }
        else
        {
            return [];
        }
    }

    public function getProjectPath(): string
    {
        return $this->bootparams['projectPath'];
    }

    public function getRootUrl(): string
    {
        $url = '';
        $hostname = $_SERVER['HTTP_HOST'];

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $_SERVER['HTTPS'] = 'on';
        }
        
        $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $port = $_SERVER['SERVER_PORT'];
        if (($protocol == 'https' && $port == '443') || ($protocol == 'http' && $port == '80'))
        {
            $url = "{$protocol}://{$hostname}{$this->bootparams['rootPath']}";
        } else {
            $url = "{$protocol}://{$hostname}{$this->bootparams['rootPath']}";
        }

        return $url;
    }

    public function getRootPath(): string
    {
        return $this->bootparams['rootPath'];
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
