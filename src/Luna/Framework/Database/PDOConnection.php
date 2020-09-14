<?php

namespace Luna\Framework\Database;

use Luna\Framework\Database\Connection;
use Luna\Framework\Database\Exception\DatabaseException;
use Luna\Framework\Database\Exception\IntegrityConstraintViolationException;
use Serializable;

/**
 * mysql用DB接続クラス。<br />
 * mysqlとの接続を行います。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package database
 */
class PDOConnection extends Connection implements Serializable
{

    // /**
    //  * コンストラクタ
    //  *
    //  * @param    $dsn        DSN文字列
    //  * @param    $dbuser        DBユーザ名
    //  * @param    $dbpassword    DBパスワード
    //  * @param    $dbname        DB名
    //  * @param    $dbhost        ホスト名
    //  * @param    $dbport        ポート番号
    //  */
    // public function __construct(string $datasourceName, string $dsn, string $dbuser = '', string $dbpassword = '', array $options = array(), bool $newlink = false)
    // {
    //     parent::__construct($datasourceName, $dsn, $dbuser, $dbpassword, $options, $newlink);
    // }

    public function serialize()
    {
        return serialize(
            [
                'dsn' => $this->dsn,
                'dbuser' => $this->dbuser,
                'dbpassword' => $this->dbpassword,
                'options' => $this->options,
                'newlink' => $this->newlink,
            ]
            );
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->dsn = $data['dsn'];
        $this->dbuser = $data['dbuser'];
        $this->dbpassword = $data['dbpassword'];
        $this->options = $data['options'];
        $this->newlink = $data['newlink'];

        $this->connect();
    }

    // /**
    //  * 接続パラメータを設定する
    //  *
    //  * @param    $dbuser        DBユーザ名
    //  * @param    $dbpassword    DBパスワード
    //  * @param    $dbname        DB名
    //  * @param    $dbhost        ホスト名
    //  * @param    $dbport        ポート番号
    //  */
    // public function setParameters(string $dsn, string $dbuser = '', string $dbpassword = '', array $options = array(), bool $newlink = false)
    // {
    //     parent::setParameters($dsn, $dbuser, $dbpassword, $options, $newlink);
    // }

    /**
     * データベースに接続する
     */
    public function connect()
    {
        $connectOptions = [];
        foreach ($this->options as $key => $value) {
            if (strpos($key, '!php/const') === 0) {
                list(, $constKey) = \explode(' ', $key);
                $connectOptions[constant($constKey)] = $value;
            }
        }
        $this->conn = new \PDO($this->dsn, $this->dbuser, $this->dbpassword, $connectOptions);
        return $this->conn;
    }

    /**
     * CHARSETを指定する
     * @param    $charset    文字コード
     */
    public function setCharset(string $charset)
    {
        // $this->dbconn->exec("SET NAMES {$charset}");
        // $this->dbconn->exec("set CHARACTER SET {$charset}");
    }

    /**
     * データベースとの接続を閉じる
     */
    public function close()
    {
        //@mysql_close($this->dbconn);
    }

    /**
     * クエリーを実行する
     *
     * @param    $sql    クエリー
     */
    public function exec(string $sql)
    {
        $result = $this->conn->query($sql);
        
        if ($result === false) {
            $errorInfo = $this->conn->errorInfo();
            // $this->logger->error("{$errorInfo[0]}:{$errorInfo[1]}:{$errorInfo[2]}");
            if ($errorInfo[0] === '23000') {
                throw new IntegrityConstraintViolationException($errorInfo[2], $errorInfo[0]);
            } else {
                throw new DatabaseException("{$errorInfo[0]}:{$errorInfo[1]}:{$errorInfo[2]}");
            }
        }

        return $result;
    }

    /**
     * PreparedStatementを生成する
     *
     * @param string $sql    クエリー
     * @return    Statementオブジェクト
     */
    public function createStatement(string $sql, array $params = [])
    {
        $stmt = new PDOStatement($this, $sql);

        foreach ($params as $col => $val) {
            if (is_array($val)) {
                $stmt->setArray(":{$col}", $val);
            } else if (is_int($val)) {
                $stmt->setInt(":{$col}", $val);
            } else {
                $stmt->setString(":{$col}", $val);
            }
        }

        return $stmt;
    }

    public function getLastInsertId(string $idColumn = 'id')
    {
        return $this->conn->lastInsertId($idColumn);
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollback()
    {
        return $this->conn->rollback();
    }

    /**
     * SQL用にエスケープした文字列を返す
     *
     * @param string $string
     * @return void
     */
    public function escapedString( $string)
    {
        return $this->conn->quote($string);
    }

    /**
     * テーブルのレコードを全削除する
     *
     * @param string $tablename
     * @return void
     */
    public function truncate(string $tablename)
    {
        $this->conn->query("TRUNCATE {$tablename}");
    }

}
