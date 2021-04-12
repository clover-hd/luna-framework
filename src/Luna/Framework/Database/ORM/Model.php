<?php

namespace Luna\Framework\Database\ORM;

use Luna\Framework\Database\Connection;
use Luna\Framework\Database\DataSource;
use Luna\Framework\Database\Exception\RecordNotFoundException;
use Luna\Framework\Database\ORM\SQLBuilder;
use Luna\Framework\Log\Logger;
use Psr\Log\LoggerAwareTrait;

class Model
{
    use LoggerAwareTrait;

    /**
     * Connection
     *
     * @var Connection
     */
    private $connection = null;
    /**
     * ReadOnlyConnection
     *
     * @var Connection
     */
    private $readOnlyConnection = null;
    /**
     * データソース名
     *
     * @var string
     */
    private $datasourceName = 'default';
    /**
     * SELECTカラムリスト
     */
    private $select = ['*'];
    /**
     * DISTINCTフラグ
     * true = DISTINCTあり
     * false = DISTINCTなし
     *
     * @var boolean
     */
    private $distinctFlag = false;
    /**
     * テーブル名。getTablename()で返される値
     *
     * @var string
     */
    protected $tablename = '';
    /**
     * テーブル別名
     *
     * @var string
     */
    protected $as = '';
    /**
     * FROMのモデル
     * 未設定時はgetTablename()
     *
     * @var Model
     */
    private $fromModel = null;
    /**
     * JOINするモデル
     *
     * @var array
     */
    private $joinModels = array();
    /**
     * WHERE句の条件
     *
     * @var array
     */
    private $where = array();
    /**
     * ORDER BYのカラムリスト
     *
     * @var array
     */
    private $orderBys = array();
    /**
     * GROUP BYのカラムリスト
     *
     * @var array
     */
    private $groupBys = array();
    /**
     * OFFSET値
     *
     * @var int
     */
    private $offsetValue = null;
    /**
     * LIMIT値
     *
     * @var int
     */
    private $limitValue = null;
    /**
     * UPDATEで更新するカラムと値のリスト
     *
     * @var array
     */
    private $updateColumns = array();
    /**
     * INSERTで登録するカラムと値のリスト
     *
     * @var array
     */
    private $insertColumns = array();

    /**
     * 主キーカラム名
     *
     * @var array
     */
    protected $primaryKeyField = ['id'];
    /**
     * 主キーがシーケンス値かどうか
     *
     * @var bool
     */
    protected $primaryKeySequance = true;
    /**
     * テーブルの論理削除フラグ名
     * サブクラスでオーバーライドすることで変更
     *
     * @var string
     */
    protected $deleteFlagField = 'delete_flag';
    /**
     * レコード作成日時カラム名
     * サブクラスでオーバーライドすることで変更
     *
     * @var string
     */
    protected $createdAtField = 'created_at';
    /**
     * レコード更新日時カラム名
     * サブクラスでオーバーライドすることで変更
     *
     * @var string
     */
    protected $updatedAtField = 'updated_at';

    /**
     * カラムと値の連想配列
     *
     * @var array
     */
    protected $values = [];

    /**
     * 変更前の値の連想配列
     *
     * @var array
     */
    protected $originalValues = [];

    /**
     * DBから読み込んだ値か
     *
     * @var boolean
     */
    protected $dbReadFlag = false;

    /**
     * シーケンス値がカスタムの場合のSQL
     */
    protected $customSequanceQuery = '';

    /**
     * シーケンス値がカスタムの場合のカラム名
     */
    protected $customSequanceField = '';

    /**
     * コンストラクタ
     *
     * @param string $datasourceName データソース名
     */
    public function __construct(string $datasourceName = 'default')
    {
        $this->datasourceName = $datasourceName;
        // Read/Writeデータソース
        $this->connection = DataSource::getDataSource($datasourceName);
        // ReadOnlyデータソース
        $this->readOnlyConnection = DataSource::getReadOnlyDataSource($datasourceName);
        $this->setLogger(new Logger());

    }

    /**
     * Connectionを返す
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * ReadOnlyConnectionを返す
     */
    public function getReadOnlyConnection(): Connection
    {
        return $this->readOnlyConnection;
    }

    /**
     * Modelのインスタンスを返す
     *
     * @param   $datasourceName データソース名
     * @return  Model
     */
    public static function getInstance(string $datasourceName = 'default')
    {
        return new static($datasourceName);
    }

    /**
     * Modelのインスタンスを返すgetInstance()の別名
     *
     * @param   $datasourceName データソース名
     * @return  static
     */
    public static function instance(string $datasourceName = 'default')
    {
        return static::getInstance($datasourceName);
    }

    public function getTablenameWithAs()
    {
        if (empty($this->as)) {
            return $this->getTablename();
        } else {
            return $this->as;
        }
    }

    /**
     * モデルで使用するテーブル名を返す
     * デフォルトではモデルクラス名の複数形
     *
     * @return  string
     */
    public function getTablename()
    {
        if (empty($this->tablename) === false) {
            return $this->tablename;
        } else {
            $class = \explode('\\', get_called_class());
            $className = array_pop($class);
            $className = \preg_replace('/([A-Z]{1})([a-z0-9]+)([A-Z]+[a-z0-9])/u', '\1\2_\3', $className) . 's';
            return \strtolower($className);
        }
    }

    /**
     * テーブルの別名を設定する
     *
     * @param string $as
     * @return static
     */
    public function as(string $as)
    {
        $this->as = $as;
        return $this;
    }

    /**
     * テーブルの別名を返す
     *
     * @return string
     */
    public function getAs()
    {
        return $this->as;
    }

    /**
     * 引数の値をカラムにセットする
     *
     * @param array $values
     * @return static
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * 引数の連想配列をカラムの値としてモデルに保存する
     *
     * @param array $values
     * @return void
     */
    public function attachValues(array $values)
    {
        $this->values = $values;
        $this->originalValues = $values;
        // DB読み込みフラグ
        // save()のときにINSERTかUPDATEかの判断に使用
        $this->dbReadFlag = true;
    }

    public function truncate()
    {
        $this->connection->truncate($this->getTablename());
    }

    /**
     * モデルをDBに保存する
     *
     * @return void
     */
    public function save()
    {
        if ($this->dbReadFlag) {
            // UPDATE
            $wheres = [];
            $whereValues = [];
            foreach ($this->primaryKeyField as $column) {
                $wheres[] = " {$column} = :{$column} ";
                $whereValues[$column] = $this->originalValues[$column];
            }
            $updateValues = [];
            foreach ($this->values as $col => $val) {
                if ($this->originalValues[$col] !== $val) {
                    $updateValues[$col] = $val;
                }
            }
            if (count($updateValues) > 0) {
                $this->whereAnd(
                    implode(" AND ", $wheres),
                    $whereValues
                )->update(
                    $updateValues
                );
            }
            $this->originalValues = array_merge($this->originalValues, $this->values);
            $this->values = [];
        } else {
            // INSERT
            $id = $this->insert(
                $this->values
            );
            if ($id > 0) {
                $this->values[$this->primaryKeyField[0]] = $id;
            }
            $this->originalValues = $this->values;
            $this->values = [];
        }
    }

    /**
     * 指定したカラムの値を返す
     *
     * @param string $columnName
     * @return mixed
     */
    public function get(string $columnName)
    {
        if (isset($this->values[$columnName])) {
            return $this->values[$columnName];
        } else if (isset($this->originalValues[$columnName])) {
            return $this->originalValues[$columnName];
        } else {
            return null;
        }
    }

    /**
     * カラムに値をセットする
     *
     * @param string $columnName
     * @param mixed $value
     * @return void
     */
    public function set(string $columnName, $value)
    {
        $this->values[$columnName] = $value;
    }

    /**
     * カラムに値がセットされていればその値を、セットされていなければfalseを返す
     *
     * @param string $columnName
     * @return mixed
     */
    public function __isset(string $columnName)
    {
        if (isset($this->values[$columnName])) {
            return $this->values[$columnName];
        } else if (isset($this->originalValues[$columnName])) {
            return $this->originalValues[$columnName];
        } else {
            return false;
        }
    }

    /**
     * モデル->カラム名で値を取得する
     *
     * @param string $columnName
     * @return mixed
     */
    public function __get(string $columnName)
    {
        return $this->get($columnName);
    }

    /**
     * モデル->カラム名で値を保存する
     *
     * @param string $columnName
     * @param mixed $value
     */
    public function __set(string $columnName, $value)
    {
        $this->set($columnName, $value);
    }

    public function getPrimaryValue(string $glue = '-'): string
    {
        $values = [];
        foreach ($this->primaryKeyField as $col) {
            $values[] = $this->$col;
        }

        return implode($glue, $values);
    }

    /**
     * カラム名と値と連想配列にして返す
     *
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }

    /**
     * SELECT文のカラムリストを返す
     *
     * @return  array
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * DISTINCTフラグを返す
     *
     * @return boolean
     */
    public function getDistinct()
    {
        return $this->distinctFlag;
    }

    /**
     * SELECT文のカラムリストをセットする
     *
     * @params  $select
     */
    public function setSelect(array $select)
    {
        $this->select = $select;
    }

    /**
     * FROMに設定されているModelを返す
     *
     * @return Model
     */
    public function getFromModel()
    {
        return $this->fromModel;
    }

    /**
     * WHERE句の設定値を返す
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * WHERE句の設定値をセットする
     *
     * @param array $where
     * @return void
     */
    public function setWhere(array $where)
    {
        $this->where = $where;
    }

    /**
     * WHERE句で指定されているパラメータを返す
     *
     * @return  array
     */
    public function getWhereParams()
    {
        return (is_null($this->where) === false && isset($this->where['where']) && is_array($this->where['params'])) ? $this->where['params'] : [];
    }

    /**
     * WHERE句の条件が指定されているか返す
     *
     * @return  bool
     */
    public function hasWhere()
    {
        return (is_null($this->where) === false && count($this->where) > 0 && $this->where['where'] !== '');
    }

    public function getOrderBys()
    {
        return $this->orderBys;
    }

    public function getGroupBys()
    {
        return $this->groupBys;
    }

    public function getLimit()
    {
        return $this->limitValue;
    }

    public function getOffset()
    {
        return $this->offsetValue;
    }

    public function getJoinModels()
    {
        return $this->joinModels;
    }

    public function getUpdateColumns()
    {
        return $this->updateColumns;
    }

    public function getInsertColumns()
    {
        return $this->insertColumns;
    }

    public function getCustomSequanceQuery(): string
    {
        return $this->customSequanceQuery;
    }

    public function getCustomSequanceField(): string
    {
        return $this->customSequanceField;
    }

    public function getRecordSet(): ResultSet
    {
        $sql = SQLBuilder::select($this);
        $stmt = $this->readOnlyConnection->createStatement($sql);
        $result = $stmt->execute();
        $result->setModel(get_class($this), $this->readOnlyConnection->getDatasourceName());
        return $result;
    }

    /**
     * SELECT文を実行する
     *
     * @param  $select
     * @return  static
     */
    public function select(array $select = ['*'])
    {
        $this->select = $select;

        return $this;
    }

    /**
     * DISTINCTを設定する
     *
     * @return static
     */
    public function distinct()
    {
        $this->distinctFlag = true;
        return $this;
    }

    /**
     * 指定したIDを持つレコードを返します
     *
     * @param  array|mixid $id
     * @return static
     */
    public function findOne($id)
    {
        if (is_array($id) === false) {
            $id = [$id];
        }
        $where = [];
        $whereParams = [];
        foreach ($this->primaryKeyField as $idx => $primaryKey) {
            $where[] = "{$primaryKey} = :_primary_key{$idx}";
            $whereParams["_primary_key{$idx}"] = $id[$idx];
        }
        return $this->whereAnd(
            implode(' AND ', $where),
            $whereParams
        )
            ->getRecordSet()
            ->getFirst();
    }

    /**
     * 指定した条件のレコードを返す。レコードがない場合は新しい空の未保存レコードを返す
     *
     * @param string $where
     * @param array $params
     * @return static
     */
    public function findOrCreate(string $where, array $params = [])
    {
        try {
            $model = $this->whereAnd($where, $params)
                ->getRecordSet()
                ->getFirst();
        } catch (RecordNotFoundException $ex) {
            $this->whereAnd($where, $params);
            $model = $this->getInstance($this->datasourceName);
        }
        return $model;
    }

    /**
     * findOrCreateでCreateしたかどうかを返す
     * @return boolean
     */
    public function isCreated()
    {
        return $this->dbReadFlag == false;
    }

    /**
     * INSERT文を実行する
     *
     * @param  $columns    登録カラム
     * [
     *  'column1' => $val1,
     *  'column2' => $val2,
     *  'column3' => $val3
     * ]
     */
    public function insert(array $columns = [])
    {
        $this->insertColumns = $columns;
        $sql = SQLBuilder::insert($this);
        $stmt = $this->connection->createStatement($sql, $columns);
        $result = $stmt->execute();
        if ($this->primaryKeySequance === true) {
            return $this->connection->getLastInsertId();
        } else {
            return 0;
        }
    }

    /**
     * UPDATE文を実行する
     *
     * @params  $columns    更新カラム
     * [
     *  'column1' => $val1,
     *  'column2' => $val2,
     *  'column3' => $val3
     * ]
     */
    public function update(array $columns = [])
    {
        // $params = array_merge($this->where['params'], $columns);
        $this->updateColumns = $columns;
        $sql = SQLBuilder::update($this);
        $stmt = $this->connection->createStatement($sql, $columns);
        $result = $stmt->execute();
        return $result;
    }

    /**
     * 論理/物理削除を行う
     * delete_flagがModelで指定されていれば論理削除を行い、指定されていなければ物理削除を行う
     */
    public function delete()
    {
        if ($this->deleteFlagField == '') {
            // delete_flagがない場合は物理削除
            return $this->forceDelete();
        } else {
            // delete_flagがあれば論理削除
            return $this->update([
                $this->deleteFlagField => '1',
            ]);
        }
    }

    /**
     * 物理削除を行う
     */
    public function forceDelete()
    {
        $sql = SQLBuilder::delete($this);
        $stmt = $this->connection->createStatement($sql);
        $result = $stmt->execute();
        return $result;
    }

    /**
     * COUNT文を実行する
     *
     * @return  int
     */
    public function count(): int
    {
        $select = $this->select;
        $this->select = ['COUNT(*) AS C'];
        $sql = SQLBuilder::select($this);
        $stmt = $this->readOnlyConnection->createStatement($sql);
        $result = $stmt->execute();
        $this->select = $select;

        $result->setModel(get_class($this), $this->readOnlyConnection->getDatasourceName());
        $result->next();
        if ($result->valid()) {
            return $result->current()->C;
        }
        return null;
    }
    /**
     * LIMIT,OFFSETを無視し検索条件に合うレコード件数を取得する
     *
     * @return  int
     */
    public function totalCount(): int
    {
        $select = $this->select;
        $limit = $this->limitValue;
        $offset = $this->offsetValue;
        $this->select = ['COUNT(*) AS C'];
        $this->limitValue = null;
        $this->offsetValue = null;
        $sql = SQLBuilder::select($this);
        $stmt = $this->readOnlyConnection->createStatement($sql);
        $result = $stmt->execute();
        $this->select = $select;
        $this->limitValue = $limit;
        $this->offsetValue = $offset;

        $result->setModel(get_class($this), $this->readOnlyConnection->getDatasourceName());
        $result->next();
        if ($result->valid()) {
            return $result->current()->C;
        }
        return null;
    }

    /**
     * WHERE句で指定した条件のレコードがあれば更新、なければ追加しModelを返す
     *
     * @param   $columns    更新カラムと値
     * @return  static
     */
    // public function updateOrCreate(array $columns)
    // {
    //     $this->connection->beginTransaction();
    //     try
    //     {
    //         // 対象のレコードを検索する
    //         $record = $this->getRecordSet()->getFirst();
    //         $record->setValues($columns);
    //         $record->save();
    //     } catch (RecordNotFoundException $e) {
    //         $record = $this->getInstance($this->datasourceName);
    //         $record->setValues($columns);
    //         $record->save();
    //     }
    //     $this->connection->commit();

    //     return $record;
    // }

    /**
     * FROMにModelを指定する
     * SQLではサブクエリが挿入される
     *
     * @param   $fromModel  Model
     * @param   @as ModelのFROMでの別名(AS)
     * @return static
     */
    public function fromModel(Model $fromModel, string $as)
    {
        $this->fromModel = [
            'model' => $fromModel,
            'as' => $as,
        ];
        return $this;
    }

    /**
     * (INNER) JOINにModelを指定する
     * SQLではサブクエリが挿入される
     *
     * @param   $fromModel  Model
     * @param   $on  JOIN条件['table1.col1', '=', 'table2.col2']
     * @param   @as ModelのFROMでの別名(AS)
     * @return static
     */
    public function joinModel(Model $joinModel, string $as, string $on)
    {
        $this->joinModels[] = [
            'model' => $joinModel,
            'on' => $on,
            'as' => $as,
            'type' => 'JOIN',
        ];
        return $this;
    }

    /**
     * LEFT (OUTER) JOINにModelを指定する
     * SQLではサブクエリが挿入される
     *
     * @param   $fromModel  Model
     * @param   $on  JOIN条件['table1.col1', '=', 'table2.col2']
     * @param   @as ModelのFROMでの別名(AS)
     * @return static
     */
    public function leftJoinModel(Model $joinModel, string $as, string $on)
    {
        $this->joinModels[] = [
            'model' => $joinModel,
            'as' => $as,
            'on' => $on,
            'type' => 'LEFT JOIN',
        ];
        return $this;
    }

    /**
     * RIGHT (OUTER) JOINにModelを指定する
     * SQLではサブクエリが挿入される
     *
     * @param   $fromModel  Model
     * @param   $on  JOIN条件['table1.col1', '=', 'table2.col2']
     * @param   @as ModelのFROMでの別名(AS)
     * @return static
     */
    public function rightJoinModel(Model $joinModel, string $as, string $on)
    {
        $this->joinModels[] = [
            'model' => $joinModel,
            'as' => $as,
            'on' => $on,
            'type' => 'RIGHT JOIN',
        ];
        return $this;
    }

    /**
     * 論理削除されたデータを除く
     *
     * @return  static
     */
    public function alive()
    {
        if ($this->deleteFlagField != '') {
            return $this->whereAnd(
                "{$this->getTablenameWithAs()}.{$this->deleteFlagField} = :{$this->getTablenameWithAs()}_{$this->deleteFlagField}",
                [
                    "{$this->getTablenameWithAs()}_{$this->deleteFlagField}" => '0',
                ]
            );
        }
        return $this;
    }

    /**
     * 論理削除されたデータ
     *
     * @return  static
     */
    public function dead()
    {
        if ($this->deleteFlagField != '') {
            return $this->whereAnd(
                "{$this->deleteFlagField} = :$this->deleteFlagField",
                [
                    $this->deleteFlagField => '1',
                ]
            );
        }
        return $this;
    }

    /**
     * 全件取得
     *
     * @return  static
     */
    public function all()
    {
        $this->where = [
        ];
        return $this;
    }

    /**
     * 検索条件
     *
     * @param    $where    WHERE句の条件文<br />例)"email like :email and username like :username"
     * @param   $params $whereの条件文に指定する値<br />例){ email : "%mail@example.com", username : "%abc%" }
     * @return  static
     */
    public function where(string $where = '', array $params = [])
    {
        $this->where = [
            'where' => " ({$where}) ",
            'params' => $params,
        ];

        return $this;
    }

    /**
     * 検索条件
     *
     * @param    $where    WHERE句の条件文<br />例)"email like :email and username like :username"
     * @param   $params $whereの条件文に指定する値<br />例){ email : "%mail@example.com", username : "%abc%" }
     * @return  static
     */
    public function whereAnd(string $where = '', array $params = [])
    {
        if (isset($this->where['where'])) {
            $this->where = [
                'where' => $this->where['where'] . " AND ({$where}) ",
                'params' => array_merge($this->where['params'], $params),
            ];
        } else {
            $this->where = [
                'where' => " ({$where}) ",
                'params' => $params,
            ];
        }

        return $this;
    }

    /**
     * 検索条件
     *
     * @param    $where    WHERE句の条件文<br />例)"email like :email and username like :username"
     * @param   $params $whereの条件文に指定する値<br />例){ email : "%mail@example.com", username : "%abc%" }
     * @return  static
     */
    public function whereOr(string $where = '', array $params = [])
    {
        if (isset($this->where['where'])) {
            $this->where = [
                'where' => $this->where['where'] . " OR ({$where}) ",
                'params' => array_merge($this->where['params'], $params),
            ];
        } else {
            $this->where = [
                'where' => " ({$where}) ",
                'params' => $params,
            ];
        }

        return $this;
    }

    /**
     * 表示順
     *
     * @param   $orderBys
     * @return  static
     */
    public function orderBy(array $orderBys)
    {
        $this->orderBys = $orderBys;
        return $this;
    }

    /**
     * 表示順
     *
     * @param   $groupBys
     * @return  static
     */
    public function groupBy(array $groupBys)
    {
        $this->groupBys = $groupBys;
        return $this;
    }

    /**
     * 取得件数
     *
     * @param integer $limit
     * @return  static
     */
    public function limit(int $limit): Model
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * オフセット
     *
     * @param integer $offset
     * @return  static
     */
    public function offset(int $offset): Model
    {
        $this->offsetValue = $offset;
        return $this;
    }


    /**
     * Geometry型のデータを返す
     *
     * @param string $column
     * @return Luna\Framework\Database\Type\Geometry
     */
    public function createGeometry($latitude, $longitude)
    {
        return $this->connection->createGeometry()
            ->setLatitude($latitude)
            ->setLongitude($longitude);
    }

    /**
     * Geometry型のデータを返す
     *
     * @param string $column
     * @return Luna\Framework\Database\Type\Geometry
     */
    public function getGeometry($column)
    {
        return $this->connection->createGeometry()
            ->fromNativeValue($this->$column);
    }
}
