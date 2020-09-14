<?php

namespace Luna\Framework\Database;

use Luna\Framework\Log\Logger;
use Psr\Log\LoggerAwareTrait;

/**
 * プリコンパイルSQL文を表すクラスです。<br />
 * すべてのプリコンパイルSQL文クラス階層のルートです。
 *
 * @author Takmichi Suzuki
 * @version 1.0
 * @package database
 */
class Statement
{
    use LoggerAwareTrait;

    protected $conn;
    protected $sql;
    protected $values;
    protected $types;
    protected $value_pos;
    protected $result;
    protected $resultArray;
    protected $resultFields;
    protected $limit;
    protected $offset;
    protected $logger;
    protected $affected_rows;

    /**
     * コンストラクタ
     *
     * @param    Connection $conn    DB接続オブジェクト
     * @param    string     $sql    クエリー
     */
    public function __construct(Connection $conn, string $sql)
    {
        $this->debug = false;

        $this->conn = $conn;
        $this->sql = $sql;
        $this->values = array();
        $this->types = array();
        $this->value_pos = array();
        $this->resultSet = array();
        $this->recordLimit = -1;
        $this->recordOffset = 0;
        $this->affected_rows = 0;
        $offset = 0;
        $i = 0;

        $this->setLogger(new Logger());
    }

    /**
     * クエリーを実行する
     */
    public function execute()
    {
    }

    public function setParam($index, $x, string $type)
    {
        $this->values[$index] = $x;
        $this->types[$index] = $type;
    }

    /**
     * RAW値をセットする(エスケープなどの処理を行なわない)
     *
     * @param    int        $index    値をセットする"?"の位置(1～)
     * @param    string    $x        セットする値
     */
    public function setRaw($index, string $x)
    {
        $this->setParam($index, $x, "raw");
    }

    /**
     * 文字列型の値をセットする
     *
     * @param    int        $index    値をセットする"?"の位置(1～)
     * @param    string    $x        セットする値
     */
    public function setString($index, $x)
    {
        $this->setParam($index, $x, "string");
    }

    /**
     * 文字列型の値をセットする
     *
     * @param    int        $index    値をセットする"?"の位置(1～)
     * @param    string    $x        セットする値
     */
    public function setArray($index, array $x)
    {
        $this->setParam($index, $x, "array");
    }

    /**
     * 数値型の値をセットする
     *
     * @param    int        $index    値をセットする"?"の位置(1～)
     * @param    string    $x        セットする値
     */
    public function setInt($index, int $x)
    {
        $this->setParam($index, $x, "int");
    }

    /**
     * クエリーを実行した結果を返す
     *
     * @return    array    結果
     */
    public function getResultArray()
    {
        return $this->resultArray;
    }

    /**
     * クエリーを実行した結果のカラム名リストを返す
     *
     * @return    array    カラム名リスト
     */
    public function getResultFields()
    {
        return $this->resultFields;
    }

    /**
     * 直近の INSERT、 UPDATE、REPLACE あるいは DELETE クエリにより変更された行の数を返す
     *
     * @return    int    行数
     */
    public function getAffectedRows()
    {
        return $this->affected_rows;
    }

    /**
     * このステートメントに他のステートメントを結合する
     *
     * @param    LunaPreparedStatement    $stmt    結合するオブジェクト
     */
    // public function appendStatement($stmt)
    // {
    //     if ($stmt->sql == "") {
    //         return;
    //     }

    //     $new_values = array();
    //     $new_types = array();
    //     $this->sql .= " " . $stmt->sql;

    //     $cnt = 0;
    //     reset($this->values);
    //     foreach ($this->values as $key => $value) {
    //         if (is_int($key)) {
    //             $new_values[$cnt] = $this->values[$key];
    //             $new_types[$cnt] = $this->types[$key];
    //             $cnt++;
    //         }
    //     }
    //     reset($stmt->values);
    //     foreach ($stmt->values as $key => $value) {
    //         if (is_int($key)) {
    //             $new_values[$cnt] = $stmt->values[$key];
    //             $new_types[$cnt] = $stmt->types[$key];
    //             $cnt++;
    //         }
    //     }
    //     reset($this->values);
    //     foreach ($this->values as $key => $value) {
    //         if (is_int($key) === false) {
    //             $new_values[$key] = $this->values[$key];
    //             $new_types[$key] = $this->types[$key];
    //         }
    //     }
    //     reset($stmt->values);
    //     foreach ($stmt->values as $key => $value) {
    //         if (is_int($key) === false) {
    //             $new_values[$key] = $stmt->values[$key];
    //             $new_types[$key] = $stmt->types[$key];
    //         }
    //     }
    //     $this->values = $new_values;
    //     $this->types = $new_types;

    //     $this->setValuePosition();
    // }

    protected function createExecSql(): string
    {
        return '';
    }

    public function getExecSql(): string
    {
        return $this->createExecSql();
    }

    public function getLastErrorMessage(): string
    {
        return '';
    }

    public function getInsertId(): int
    {
        return 0;
    }

    public function getCustomSequanceQuery(string $idColumnName, string $where = null, array $whereParams = null): string
    {
        return '';
    }
}
