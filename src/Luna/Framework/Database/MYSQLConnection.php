<?php

namespace Luna\Framework\Database;

use Luna\Framework\Database\Migration\MYSQLMigration;
use Luna\Framework\Database\Type\Geometry;
use Luna\Framework\Database\Type\MYSQLGeometry;
use Serializable;

/**
 * mysql用DB接続クラス。<br />
 * mysqlとの接続を行います。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package database
 */
class MYSQLConnection extends PDOConnection implements Serializable
{

    /**
     * マイグレーションクラスインスタンスを返す
     *
     * @return Luna\Framework\Database\Migration\Migration
     */
    public function getMigration()
    {
        return new MYSQLMigration();
    }

    /**
     * 現在のデータベース名を返す
     *
     * @return string
     */
    public function getCurrentDatabaseName()
    {
        $stmt = $this->createStatement('SELECT DATABASE() AS databasename', []);
        $ret = $stmt->execute();
        $result = $ret->toRawArray();

        return $result[0]['databasename'];
    }

    /**
     * PreparedStatementを生成する
     *
     * @param string $sql    クエリー
     * @return    Statementオブジェクト
     */
    public function createStatement(string $sql, array $params = [])
    {
        $stmt = new MYSQLStatement($this, $sql);

        if (is_array($params)) {
            foreach ($params as $col => $val) {
                if (is_array($val)) {
                    $stmt->setArray(":{$col}", $val);
                } else if (is_int($val)) {
                    $stmt->setInt(":{$col}", $val);
                } else if ($val instanceof Geometry) {
                    $stmt->setRaw(":{$col}", $val->toSqlValue($val));
                } else {
                    $stmt->setString(":{$col}", $val);
                }
            }
        }

        return $stmt;
    }

    public function getCustomSequanceQuery(string $tablename, string $idColumnName, string $where = null, array $values = null, bool $useLastInsertId = true): string
    {
        if ($useLastInsertId) {
            $sql = "SELECT LAST_INSERT_ID(IFNULL(MAX({$idColumnName}), 0) + 1) FROM {$tablename} AS custom_sequance_table";
        } else {
            $sql = "SELECT IFNULL(MAX({$idColumnName}), 0) + 1 FROM {$tablename} AS custom_sequance_table";
        }
        if (is_null($where) === false) {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = $this->createStatement($sql, $values);

        return $stmt->getExecSql();
    }
    

    public function createGeometry()
    {
        return new MYSQLGeometry();
    }
}
