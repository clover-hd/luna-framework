<?php

namespace Luna\Framework\Routes;

class Route
{
    private $routes;
    private $route;
    private $routePath;

    public function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Routesを返す
     *
     * @return  Routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Routeのパスとパラメータを返す
     *
     * @param $routePath  対象ルートのパス
     * @param $route  対象ルートのroutes.ymlの連想配列
     */
    public function setRoute(string $routePath, array $route)
    {
        $this->routePath = $routePath;
        $this->route = $route;
    }

    /**
     * Routeのコントローラクラス名を返す
     *
     * @return  string
     */
    public function getClassName()
    {
        return $this->route['class'];
    }

    /**
     * RouteのREQUEST_METHODを返す
     *
     * @return  string
     */
    public function getActionMethodName()
    {
        return $this->route['method'];
    }

    /**
     * Routeのパスを返す
     *
     * @return  string
     */
    public function getRoutePath()
    {
        return $this->routePath;
    }

    /**
     * RouteのURLを返す
     *
     * @return  string
     */
    public function getUrl()
    {
        return Route::normarizationUrl($this->getRoutes()->getRootUrl() . $this->routePath);
    }

    /**
     * URLの正規化を行う
     *
     * @param $url  URL
     * @return  string
     */
    public static function normarizationUrl(string $url)
    {
        $head = '';
        if (preg_match('/^http|^https/', $url) > 0)
        {  
            preg_match('(https?://[^/]+(?=/))', $url, $match);
            $head = $match[0];
            $url = preg_replace('(https?://[^/]+(?=/))', '', $url);

        } else {
            // 先頭に「/」があるか確認
            if (substr($url, 0, 1) != '/') {
                $url = "/{$url}";
            }
        }
        $uriElements = explode('/', $url);
        $last = array_pop($uriElements);
        if (strpos($last, '.') === false) {
            // URIの最後に拡張子がついていない場合は「/」があるか確認
            // なければ追加する
            if (substr($url, -1, 1) != '/') {
                $url = "{$url}/";
            }
        }

        // 「//」を「/」に置換する
        while (\preg_match('/\/\//u', $url)) {
            $url = \preg_replace('/\/\//u', '/', $url);
        }

        return $head . $url;
    }
}
