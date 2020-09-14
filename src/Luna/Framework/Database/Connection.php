<?php

namespace Luna\Framework\Database;

use Luna\Framework\Database\Migration\Migration;

/**
 * DB接続クラス。<br />
 * すべてのDB接続クラス階層のルートです。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package database
 */
class Connection
{
    const ISOLATION_LEVEL_READ_UNCOMMITTED = "read_uncommitted";
    const ISOLATION_LEVEL_READ_COMMITTED = "read_committed";
    const ISOLATION_LEVEL_REPEATABLE_READ = "repeatable_read";
    const ISOLATION_LEVEL_SERIALIZABLE = "serializable";

    protected $datasourceName;
    protected $dsn;
    protected $dbuser;
    protected $dbpassword;
    protected $options;
    protected $newlink;

    protected $conn;

    protected $logger;

    /**
     * コンストラクタ
     *
     * @param string $datasourceName データソース名
     * @param string $dbuser DBユーザ名
     * @param string $dbpassword DBパスワード
     * @param string $dbname DB名
     * @param string $dbhost ホスト名
     * @param string $dbport ポート番号
     */
    public function __construct(string $datasourceName, string $dsn, string $dbuser = '', string $dbpassword = '', array $options = array(), bool $newlink = false)
    {
        $this->datasourceName = $datasourceName;
        $this->dsn = $dsn;
        $this->dbuser = $dbuser;
        $this->dbpassword = $dbpassword;
        $this->options = $options;
        $this->newlink = $newlink;
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
    // public function setParameters(string $datasourceName, string $dsn, string $dbuser = '', string $dbpassword = '', array $options = array(), bool $newlink = false)
    // {
    //     $this->dsn = $dsn;
    //     $this->dbuser = $dbuser;
    //     $this->dbpassword = $dbpassword;
    //     $this->options = $options;
    //     $this->newlink = $newlink;
    // }

    /**
     * ログオブジェクトを設定する
     *
     * @param    $logging    ログオブジェクト
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getDatasourceName()
    {
        return $this->datasourceName;
    }

    /**
     * データベース名を返す
     * 
     * @return string
     */
    public function getCurrentDatabaseName()
    {
        return '';
    }

    /**
     * データベースに接続する
     */
    public function connect()
    {

    }

    /**
     * CHARSETを指定する
     * @param    $charset    文字コード
     */
    public function setCharset(string $charset)
    {

    }

    /**
     * データベースとの接続を閉じる
     */
    public function close()
    {

    }

    /**
     * クエリーを実行する
     *
     * @param    $sql    クエリー
     */
    public function exec(string $sql)
    {

    }

    /**
     * PreparedStatementを生成する
     *
     * @param string $sql    クエリー
     * @return    PreparedStatementオブジェクト
     */
    public function createStatement(string $sql, array $params = [])
    {

    }

    /**
     * DB接続リソースを返す
     * @return    DB接続リソース
     */
    public function getConnection()
    {
        return $this->conn;
    }

    public function getLastInsertId(string $idColumn = 'id')
    {
        return 0;
    }

    public function setIsolationLevel($level)
    {
    }

    public function beginTransaction()
    {
    }

    public function commit()
    {
    }

    public function rollback()
    {
    }

    /**
     * SQL用にエスケープした文字列を返す
     *
     * @param string $string
     * @return void
     */
    public function escapedString(string $string)
    {
        return '';
    }

    /**
     * マイグレーションクラスインスタンスを返す
     *
     * @return Luna\Framework\Database\Migration\Migration
     */
    public function getMigration()
    {
        return new Migration();
    }


    /**
     * テーブルのレコードを全削除する
     *
     * @param string $tablename
     * @return void
     */
    public function truncate(string $tablename)
    {
    }

    /**
     * カスタムシーケンス値を設定する
     * ここで設定した条件でシーケンス値が生成されます
     *
     * @param string $tablename テーブル名
     * @param string $idColumnName シーケンスカラム名
     * @param string $where WHERE句
     * @param array $values 条件の値
     * @param boolean $useLastInsertId LAST_INSERT_ID()を使用するかどうか
     * @return string シーケンス値取得用SQL
     */
    public function getCustomSequanceQuery(string $tablename, string $idColumnName, string $where = null, array $values = null, bool $useLastInsertId = true): string
    {
        return '';
    }
    

    /**
     * 空のGeometryを返す
     *
     * @return Luna\Framework|Database\Type\Geometry
     */
    public function createGeometry()
    {
        return null;
    }
}
