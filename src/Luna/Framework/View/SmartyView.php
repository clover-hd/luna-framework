<?php

namespace Luna\Framework\View;

use Luna\Framework\Application\Application;
use Luna\Framework\Controller\Controller;
use Luna\Framework\Http\Request;
use Luna\Framework\Routes\Route;
use Luna\Framework\Routes\Routes;
use Smarty;

class SmartyView extends View
{
    protected $smarty;
    protected $controller;
    protected $template;
    protected $params;

    public function __construct(Application $application, Request $request, string $template, array $params)
    {
        parent::__construct($application, $request);
        $this->template = $template;
        $this->params = $params;
    }

    public function init(Routes $routes, Route $route)
    {
        $this->smarty = new Smarty();

        $this->smarty->setCacheDir($this->application->getProjectPath() . '/tmp/cache/view_cache/');
        $this->smarty->setCompileDir($this->application->getProjectPath() . '/tmp/cache/view_compile/');
        $this->smarty->setConfigDir($this->application->getProjectPath() . '/config/');
        // $this->setPluginsDir(LIB_DIR . '/lunafw/smarty_plugins')->addPluginsDir(LIB_DIR . '/smarty_plugins')->addPluginsDir(LIB_DIR . '/smarty/plugins')->addPluginsDir(LIB_DIR . '/smarty/sysplugins');
        $this->smarty->setTemplateDir($this->application->getProjectPath() . '/resources/view/');
        $this->smarty->addPluginsDir(dirname(__FILE__) . '/Smarty/Plugins/');
        $this->smarty->left_delimiter = '{';
        $this->smarty->right_delimiter = '}';

        $configParams = $this->application->getConfig()->getConfigParams();
        $envitonment = $configParams['system']['environment'];

        // 実行環境
        $this->smarty->assign('ENVIRONMENT', $envitonment);
        $this->smarty->assign('MINIFY_JS', $this->application->getConfig()->getConfigParams()['minify'][$envitonment]['js']);
        $this->smarty->assign('MINIFY_CSS', $this->application->getConfig()->getConfigParams()['minify'][$envitonment]['css']);
        // プロジェクトディレクトリ
        $this->smarty->assign('PROJECT_PATH', $this->application->getProjectPath());
        $this->smarty->assign('ROOT_PATH', $this->application->getRootPath());
        // 静的ファイルキャッシュディレクトリ
        $this->smarty->assign('STATIC_CACHE_DIR', $this->application->getProjectPath() . '/public/cache/');

        // Routes
        $this->smarty->assign('routes', $routes);
        // Request
        $this->smarty->assign('httpRequest', $this->request);

        // システム変数
        $this->smarty->assign('ROOT_URL', $routes->getRootUrl());
        $this->smarty->assign('REQUEST_URL', $this->request->getRequestUrl());

        // HTTP変数をアサイン
        $this->smarty->assign('get', $this->request->getGet());
        $this->smarty->assign('post', $this->request->getPost());
        $this->smarty->assign('request', $this->request->getRequest());
        $this->smarty->assign('session', $this->request->getSession());
        $this->smarty->assign('cookie', $this->request->getCookie());
        $this->smarty->assign('routeParams', $this->request->getRouteparams());

        // パラメータをアサイン
        foreach ($this->params as $name => $val) {
            $this->smarty->assign($name, $val);
        }
    }

    public function render()
    {
        $this->smarty->display($this->template . '.tpl');
    }

    public function fetch()
    {
        return $this->smarty->fetch($this->template . '.tpl');
    }
}
