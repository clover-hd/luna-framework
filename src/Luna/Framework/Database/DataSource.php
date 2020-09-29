<?php

namespace Luna\Framework\Database;

use Luna\Framework\Application\Application;

/**
 * データソースクラス。<br />
 * データソース設定の読み込みから生成などを行う。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package database
 */
class DataSource
{

    static $dsArray = array();

    public function __construct()
    {
    }

    /**
     * 指定したデータベースの接続設定を返す
     *
     * @param $datasourceName   データソース名
     * @return array
     */
    public static function getDataSourceConfigration(string $datasourceName)
    {
        $config = Application::getInstance()->getConfig()->getDatasourceParams();
        $dsconfig = $config[$datasourceName];

        return $dsconfig;
    }

    /**
     * インスタンスを生成する
     *
     * @param string $datasourceName データソース名
     * @return Connection
     */
    public static function getDataSource(string $datasourceName = 'default')
    {

        if (isset(DataSource::$dsArray[$datasourceName]) == false) {
            $datasource = DataSource::getDataSourceConfigration($datasourceName);

            if ($datasource == null) {
                return null;
            }

            $dataSourceClass = $datasource['datasource_class'];
            $dbconn = new $dataSourceClass(
                $datasourceName,
                $datasource['database']['dsn'],
                $datasource['database']['user'],
                $datasource['database']['password'],
                $datasource['database']['options']
            );

            $dbconn->connect();

            DataSource::$dsArray[$datasourceName] = $dbconn;
        }

        return DataSource::$dsArray[$datasourceName];
    }

    /**
     * ReadOnly接続用インスタンスを生成する
     *
     * @param string $datasourceName データソース名
     * @return Connection
     */
    public static function getReadOnlyDataSource(string $datasourceName = 'default')
    {
        $readOnlyDatasourceName = $datasourceName . '_read';

        if (isset(DataSource::$dsArray[$readOnlyDatasourceName]) == false) {
            $datasource = DataSource::getDataSourceConfigration($readOnlyDatasourceName);

            if ($datasource == null) {
                return DataSource::getDataSourceConfigration($datasourceName);
            }

            $dataSourceClass = $datasource['datasource_class'];
            $dbconn = new $dataSourceClass(
                $readOnlyDatasourceName,
                $datasource['database']['dsn'],
                $datasource['database']['user'],
                $datasource['database']['password'],
                $datasource['database']['options']
            );

            $dbconn->connect();

            DataSource::$dsArray[$readOnlyDatasourceName] = $dbconn;
        }

        return DataSource::$dsArray[$datasourceName];
    }
}
