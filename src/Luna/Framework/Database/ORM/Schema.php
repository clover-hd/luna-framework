<?php

namespace Luna\Framework\Database\ORM;

use Luna\Framework\Database\DataSource;
use Luna\Framework\Log\Logger;
use Psr\Log\LoggerAwareTrait;

class Schema
{
    use LoggerAwareTrait;

    /**
     * Connection
     *
     * @var Luna\Framework\Database\Connection
     */
    private $connection = null;
    /**
     * データソース名
     *
     * @var string
     */
    private $datasourceName = 'default';

    /**
     * コンストラクタ
     *
     * @param string $datasourceName データソース名
     */
    public function __construct(string $datasourceName = 'default')
    {
        $this->datasourceName = $datasourceName;
        $this->connection = DataSource::getDataSource($datasourceName);
        $this->setLogger(new Logger());
    }

    /**
     * Schemaのインスタンスを返す
     *
     * @param   $datasourceName データソース名
     * @return  Schema
     */
    public static function getInstance(string $datasourceName = 'default')
    {
        return new static($datasourceName);
    }

    /**
     * Schemaのインスタンスを返すgetInstance()の別名
     *
     * @param   $datasourceName データソース名
     * @return  Schema
     */
    public static function instance(string $datasourceName = 'default')
    {
        return static::getInstance($datasourceName);
    }

    /**
     * DBにテーブルが存在するか確認する
     *
     * @param string $tablename
     * @return boolean
     */
    public function hasTable(string $tablename)
    {
        $databaseName = $this->connection->getCurrentDatabaseName();
        $sql  = 'SELECT table_catalog, table_schema, table_name, table_type FROM information_schema.tables ';
        $sql .= 'WHERE table_schema = :table_schema AND table_name = :table_name ';
        $stmt = $this->connection->createStatement(
            $sql,
            [
                'table_schema' => $databaseName,
                'table_name' => $tablename
            ]
        );
        $ret = $stmt->execute();
        $result = $ret->toRawArray();

        return is_null($result) === false && (count($result) > 0);
    }
}