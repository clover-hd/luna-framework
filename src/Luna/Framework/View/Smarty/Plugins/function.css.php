<?php

use MatthiasMullie\Minify;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.js.php
 * Type:     function
 * Name:     css
 * Purpose:  cssタグを出力します
 * -------------------------------------------------------------
 */
function smarty_function_css($params, &$smarty)
{
    if (empty($params['file'])) {
        $smarty->trigger_error("js: パラメータ 'file' がありません");
        return;
    }

    $file = $params['file'];

    // 実行環境
    $environment = $smarty->getTemplateVars('ENVIRONMENT');
    // minifyフラグ
    $minifyCss = $smarty->getTemplateVars('MINIFY_CSS');
    // アプリケーションのルートURL
    $rootUrl = $smarty->getTemplateVars('ROOT_URL');
    // アプリケーションURLのパス
    $rootPath = $smarty->getTemplateVars('ROOT_PATH');
    // プロジェクトのパス
    $projectPath = $smarty->getTemplateVars('PROJECT_PATH');
    // キャッシュディレクトリ
    $staticCacheDir = $smarty->getTemplateVars('STATIC_CACHE_DIR');
    // 指定ファイルにURLが含まれているか確認
    $pos = strpos($params['file'], $rootUrl);
    if ($pos !== false) {
        // URLが含まれていれば、URLの除いたパス部分を対象とする
        // minifyした場合のファイル名をハッシュ値で求める
        $hashName = hash('sha256', substr($params['file'], strlen($rootUrl))) . '.css';
        // ソースJSのパス
        $cssPath = $projectPath . '/public/' . substr($params['file'], strlen($rootUrl));
        // キャッシュファイル名
        $cacheFileName = $hashName;
        // キャッシュファイルのパス
        $cacheFilePath = $staticCacheDir . '/' . $hashName;
        // キャッシュファイルのURLパス
        $cacheUrl = $rootUrl . 'cache/' . $cacheFileName;
    } else {
        // URLが含まれていなければ、そのままパス部分を対象とする
        // minifyした場合のファイル名をハッシュ値で求める
        $hashName = hash('sha256', $params['file']) . '.css';
        // ソースJSのパス
        $cssPath = $projectPath . '/public/' . $params['file'];
        // キャッシュファイル名
        $cacheFileName = $hashName;
        // キャッシュファイルのパス
        $cacheFilePath = $staticCacheDir . '/' . $hashName;
        // キャッシュファイルのURLパス
        $cacheUrl = $rootUrl . 'cache/' . $cacheFileName;
    }
    // minify処理
    if ($minifyCss === true) {
        // キャッシュファイルが存在していればタイムスタンプを取得
        if (file_exists($cacheFilePath)) {
            // キャッシュファイルのタイムスタンプを取得
            $cacheMTime = filemtime($cacheFilePath);
        } else {
            $cacheMTime = 0;
        }
        // ソースファイルのタイムスタンプを取得
        $orgMTime = filemtime($cssPath);
        // キャッシュファイルがソースファイルより古ければminify実行しファイル更新
        if ($cacheMTime < $orgMTime) {
            $minifier = new Minify\CSS($cssPath);
            file_put_contents($cacheFilePath, $minifier->minify());
        }
        // キャッシュファイルのタイムスタンプ
        $mtime = filemtime($cacheFilePath);
        // URLをキャッシュファイルに変更する
        $file = $cacheUrl;
    } else {
        // minifyしないのでソースファイルのタイムスタンプ
        $mtime = filemtime($cssPath);
    }

    // 作成したキャッシュファイル、またはソースファイルとそのタイムスタンプを結合してタグを出力する
    return '<link href="' . $file . '?' . $mtime . '" rel="stylesheet">';
}
