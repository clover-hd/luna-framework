<?php

namespace Luna\Framework\Routes;

use Luna\Framework\Application\Application;
use Luna\Framework\Http\Request;

class Routes
{
    private static $routes;

    protected $application;
    protected $rootUrl;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * アプリケーションルートURLを返す
     */
    public function getRootUrl()
    {
        return $this->application->getRootUrl();
    }

    /**
     * URIからルートを検索する
     *
     * @param Request $request  Request
     * @param string $uri
     * @return  Route
     */
    public function getRouteByUri(Request $request, string $url, string $method = null)
    {
        $confRoutes = $this->application->getConfig()->getRouteParams();

        if (is_null($method)) {
            $method = $request->getRequestMethod();
        }

        // $route = new Route($this);

        // URIに含まれるクエリパラメータ部分を削除する
        $pos = strpos($url, '?');
        if ($pos !== false) {
            $url = substr($url, 0, $pos);
        }
        $url = Route::normarizationUrl($url);

        foreach ($confRoutes['routes'] as $name => $confRoute) {
            // echo $route['path'] . "<br />";
            // ルートのパラメータ指定部分を切り離す
            // $routeUrlPath = preg_replace('/(?<=<)(.+?)(?=>)/', '(\2)', $route['path']);
            $routeUrlPath = preg_replace('/(<)([a-zA-Z0-9_]+:)(.+?)(>)/', '\3', $confRoute['path']);
            if ($routeUrlPath == '') {
                $routeUrlPath = '/';
            }
            $routeUrlPath = Route::normarizationUrl($routeUrlPath);
            // パスが一致しているかどうか
            // if (strpos($url, $routeUrlPath) === 0) {
            $regexMatch = '/^' . str_replace('/', '\/', $routeUrlPath) . '$/';
            if (preg_match($regexMatch, $url, $urlMatches) === 1) {
                if (preg_match_all('/(?<=<).+?(?=>)/', $confRoute['path'], $paramMatches) > 0) {
                    $patternFailed = false;
                    array_shift($urlMatches);
                    foreach ($paramMatches[0] as $idx => $m) {
                        list($paramName, $paramPattern) = explode(':', $m, 2);
                        $param = array_shift($urlMatches);
                        if (preg_match('/^(' . $paramPattern . ')$/', $param) > 0) {
                            $params[$paramName] = $param;
                        } else {
                            $patternFailed = true;
                        }
                    }
                    
                    if ($patternFailed === false && count($urlMatches) === 0) {
                        if (isset($confRoute[$method])) {
                            // リクエストメソッドも一致していたらルート確定
                            $request->setRouteParams($params);
                            $currentRoute = new Route($this);
                            $currentRoute->setRoute($url, $confRoute[$method]);
                            return $currentRoute;
                        }
                    }
                // }


                // // URIからパス以降のパラメータ部分を抽出する
                // $urlParam = str_replace($routeUrlPath, '', $url);
                // $urlParams = explode('/', $urlParam);
                // // パス内に含まれるパラメータを取得する
                // $params = [];
                // $patternFailed = false;
                // if (preg_match_all('/(?<=<).+?(?=>)/', $route['path'], $match) > 0) {
                //     foreach ($match[0] as $idx => $m) {
                //         list($paramName, $paramPattern) = explode(':', $m);
                //         if (preg_match("/{$paramPattern}/", $urlParams[$idx], $paramMatch) === 1) {
                //             $params[$paramName] = $paramMatch[0];
                //         }
                //     }

                //     // パターンが全て一致していたらリクエストメソッドの確認
                //     if ($patternFailed === false) {
                //         if (isset($route[$method])) {
                //             // リクエストメソッドも一致していたらルート確定
                //             $request->setRouteParams($params);
                //             $currentRoute = new Route($this);
                //             $currentRoute->setRoute($url, $route[$method]);
                //             return $currentRoute;
                //         }
                //     }
                } else {
                    // パラメータがない場合は完全一致で確定
                    if ($url == $routeUrlPath) {
                        if (isset($confRoute[$method])) {
                            $currentRoute = new Route($this);
                            $currentRoute->setRoute($url, $confRoute[$method]);
                            return $currentRoute;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * 指定したルートパスのFDQNを返す
     *
     * @param Request $request
     * @param string $routePath
     * @param string $method
     * @return string
     */
    public function getRouteUrl(Request $request, string $routePath, string $method = 'get')
    {
        return $this->getRouteByUri($request, $routePath, $method)->getUrl();
    }
}
