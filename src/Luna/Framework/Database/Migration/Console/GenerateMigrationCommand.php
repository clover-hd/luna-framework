<?php

namespace Luna\Framework\Database\Migration\Console;

use Console_CommandLine;
use Exception;
use Luna\Framework\Application\Application;
use Luna\Framework\Console\Command;
use Luna\Framework\Database\DataSource;
use Luna\Framework\Http\Request;
use Symfony\Component\Yaml\Yaml;

class GenerateMigrationCommand extends Command
{
    /**
     * DB接続オブジェクト
     *
     * @var Luna\Framework\Database\Connection
     */
    private $connection;

    public function handle(Request $request)
    {
        // コマンドライン引数処理
        $parser = new Console_CommandLine(
            [
                'description' => 'generate migration command',
                'version' => '1.0.0',
            ]
        );
        $parser->addOption(
            'table_name',
            [
                'short_name' => '-t',
                'long_name' => '--table_name',
                'description' => 'table name',
                'action' => 'StoreString',
            ]
        );
        // 引数にテーブル名が指定されていれば対象のテーブルのみ出力
        $tableName = '';
        $result = $parser->parse();
        if (isset($result->options['table_name'])) {
            $tableName = $result->options['table_name'];
        }

        // DB接続取得
        $this->connection = DataSource::getDataSource();
        // DB名
        $tableSchema = $this->connection->getCurrentDatabaseName();

        // アプリケーションroot
        $projectPath = Application::getInstance()->getProjectPath();

        // 出力するファイルのプレフィクス(年月日+6桁の連番)yyyymmddxxxxxx_tablename.yml
        $filePrefix = date('Ymd');
        $fileIndex = 0;

        try {
            $tables = [];
            $tableArray = $this->getTables($tableSchema, $tableName);
            foreach ($tableArray as $table) {

                $columnsArray = $this->getTableColumns($tableSchema, $table['table_name']);
                $primaryKeyArray = $this->getTablePrimaryKey($tableSchema, $table['table_name']);
                $tableIndexArray = $this->getTableIndexList($tableSchema, $table['table_name']);
                
                $columns = [];
                $primaryKey = [];
                $indexes = [];

                $tableData = [];

                // エンジン
                $tableData['engine'] = $table['engine'];

                // 主キー
                foreach ($primaryKeyArray as $key) {
                    $primaryKey[] = $key['column_name'];
                }

                if (count($primaryKey) > 0) {
                    $tableData['primary_key'] = $primaryKey;
                }

                // インデックス
                foreach ($tableIndexArray as $index) {
                    $tmpIndex = [];
                    $indexArray = $this->getTableIndex($tableSchema, $table['table_name'], $index['index_name']);

                    $constraintArray = $this->getConstraint($tableSchema, $table['table_name'], $index['index_name']);

                    $tmpIndex['name'] = $index['index_name'];

                    // 制約
                    if (count($constraintArray) > 0) {
                        $tmpIndex['type'] = $constraintArray[0]['constraint_type'];
                    }
                    // カラム
                    $tmpIndex['columns'] = [];
                    foreach ($indexArray as $indexColumns) {
                        $tmpIndex['columns'][] = $indexColumns['column_name'];
                    }

                    $indexes[] = $tmpIndex;
                }

                if (count($indexes) > 0) {
                    $tableData['index'] = $indexes;
                }


                foreach ($columnsArray as $col) {
                    $colData = [];
                    $colData['name'] = $col['column_name'];
                    $colData['type'] = $col['data_type'];
                    // カラムサイズ
                    if ($colData['type'] == 'varchar' || $colData['type'] == 'char') {
                        $colData['size'] = intval($col['character_maximum_length']);
                    } else if ($colData['type'] == 'decimal') {
                        $colData['size'] = "{$col['numeric_precision']}, {$col['numeric_scale']}";
                    }
                    // デフォルト
                    if ($col['column_default'] == 'NULL') {
                        $colData['default'] = null;
                    } else if (empty($col['column_default']) == false) {
                        $colData['default'] = str_replace("'", "", $col['column_default']);
                    }
                    // AUTO_INCREMENT
                    if ($col['extra'] == 'auto_increment') {
                        $colData['increment'] = true;
                    }
                    // on_update
                    if (strstr($col['extra'], 'on update') !== false ) {
                        $colData['on_update'] = substr($col['extra'], 10);
                    }
                    // not null
                    if ($col['is_nullable'] == 'YES') {
                        $colData['not_null'] = false;
                    }
                    // コメント
                    $colData['comment'] = $col['column_comment'];

                    // 主キー
                    if ($col['primary_key'] == 1) {
                        $colData['primary_key'][] = $col['column_name'];
                    }

                    

                    $columns[] = $colData;
                }

                $tableData['columns'] = $columns;

                $tables[$table['table_name']] = $tableData;

                $yamlData = [
                    'up' => [
                        'create' => [
                            'table' => [
                                $table['table_name'] => $tableData
                            ]
                        ]
                    ],
                    'down' => [
                        'drop' => [
                            'table' => [
                                $table['table_name']
                            ]
                        ]
                    ]
                ];

                $yaml = Yaml::dump($yamlData, 10, 2);
                $filename = $filePrefix . sprintf('%06d', ++$fileIndex) . '_' . $table['table_name'] . '.yml';
                file_put_contents($projectPath . '/db/migrations/' . $filename, $yaml);
            }


        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     * 対象のDB(tableSchema)のテーブルを取得する
     *
     * @param string $tableSchema
     * @param string $tableName
     * @return array
     */
    protected function getTables($tableSchema, $tableName = null)
    {
        $sql  = "SELECT table_catalog, table_schema, table_name, table_type, engine, temporary ";
        $sql .= "FROM information_schema.tables ";
        $sql .= "WHERE ";
        $sql .= "table_catalog = :table_catalog AND table_schema = :table_schema ";
        $sql .= "AND table_name <> 'db_migrations' ";
        if ($tableName) {
            $sql .= "AND table_name = :table_name";
        }
        $stmt = $this->connection->createStatement($sql);
        $stmt->setString(':table_catalog', 'def');
        $stmt->setString(':table_schema', $tableSchema);
        if ($tableName) {
            $stmt->setString(':table_name', $tableName);
        }
        
        $result = $stmt->execute();
        $tableArray = $result->toArray();

        return $tableArray;
    }

    /**
     * 対象のテーブルのカラムを取得する
     *
     * @param string $tableSchema
     * @param string $tableName
     * @return array
     */
    protected function getTableColumns($tableSchema, $tableName)
    {
        $sql  = 'SELECT c.table_name, c.column_name, c.data_type, c.character_maximum_length, c.numeric_precision, c.numeric_scale, c.column_default, c.column_comment, c.is_nullable, c.extra, c.ordinal_position, ';
        $sql .= 'CASE WHEN p.table_name IS NULL THEN 1 ELSE 0 END as primary_key ';
        $sql .= 'FROM information_schema.columns c ';
        $sql .= 'LEFT OUTER JOIN ';
        $sql .= '(SELECT ';
        $sql .= '    t.table_schema, t.table_name, k.column_name  ';
        $sql .= ' FROM information_schema.table_constraints t ';
        $sql .= ' JOIN information_schema.key_column_usage k ';
        $sql .= '     ON t.constraint_name = k.constraint_name AND t.table_schema = k.table_schema AND t.table_name = k.table_name ';
        $sql .= " WHERE t.constraint_type='PRIMARY KEY') p ";
        $sql .= " ON c.table_schema = p.table_schema AND c.table_name = p.table_name ";
        $sql .= " WHERE ";
        $sql .= " c.table_catalog = :table_catalog ";
        $sql .= " AND c.table_schema = :table_schema ";
        $sql .= " AND c.table_name = :table_name ";
        $sql .= " ORDER BY c.table_schema, c.table_name, c.ordinal_position ";
        $stmt = $this->connection->createStatement($sql);
        $stmt->setString(':table_catalog', 'def');
        $stmt->setString(':table_schema', $tableSchema);
        $stmt->setString(':table_name', $tableName);

        $result = $stmt->execute();
        $columnsArray = $result->toArray();

        return $columnsArray;
    }

    /**
     * テーブルの主キーを取得する
     *
     * @param string $tableSchema
     * @param string $tableName
     * @return array
     */
    protected function getTablePrimaryKey($tableSchema, $tableName)
    {
        $sql  = "SELECT k.table_name, k.column_name, k.ordinal_position ";
        $sql .= "FROM ";
        $sql .= "information_schema.table_constraints t ";
        $sql .= "JOIN ";
        $sql .= "information_schema.key_column_usage k ";
        $sql .= "ON t.constraint_name = k.constraint_name AND t.table_schema = k.table_schema AND t.table_name = k.table_name ";

        $sql .= "WHERE ";
        $sql .= "t.table_schema = :table_schema ";
        $sql .= "AND t.table_name = :table_name ";
        $sql .= "AND t.constraint_type = 'PRIMARY KEY' ";
        $sql .= " ORDER BY k.ordinal_position ";
        
        $stmt = $this->connection->createStatement($sql);

        $stmt->setString(':table_schema', $tableSchema);
        $stmt->setString(':table_name', $tableName);

        $result = $stmt->execute();
        $primaryKeyArray = $result->toArray();

        return $primaryKeyArray;
    }

    /**
     * テーブルのインデックスリストを取得する
     *
     * @param string $tableSchema
     * @param string $tableName
     * @return array
     */
    protected function getTableIndexList($tableSchema, $tableName)
    {
        // $sql  = "SELECT t.constraint_name, t.constraint_type ";
        // $sql .= "FROM ";
        // $sql .= "information_schema.table_constraints t ";
        // $sql .= "WHERE ";
        // $sql .= "t.table_schema = :table_schema ";
        // $sql .= "AND t.table_name = :table_name ";
        // $sql .= "AND t.constraint_type <> 'PRIMARY KEY' ";
        // $sql .= " ORDER BY t.table_name ";
        
        $sql  = "SELECT DISTINCT s.index_name, s.index_type, s.index_comment  ";
        $sql .= "FROM ";
        $sql .= "information_schema.statistics s ";
        $sql .= "WHERE ";
        $sql .= "s.table_schema = :table_schema ";
        $sql .= "AND s.table_name = :table_name ";
        $sql .= "AND s.index_name <> 'PRIMARY' ";
        $sql .= " ORDER BY s.table_name ";
        
        $stmt = $this->connection->createStatement($sql);

        $stmt->setString(':table_schema', $tableSchema);
        $stmt->setString(':table_name', $tableName);

        $result = $stmt->execute();
        $indexArray = $result->toArray();

        return $indexArray;
    }

    /**
     * インテックス定義を取得する
     *
     * @param string $tableSchema
     * @param string $tableName
     * @param string $indexName
     * @return array
     */
    protected function getTableIndex($tableSchema, $tableName, $indexName)
    {
        
        $sql  = "SELECT s.index_name, s.index_type, s.column_name, s.comment, s.seq_in_index  ";
        $sql .= "FROM ";
        $sql .= "information_schema.statistics s ";
        $sql .= "WHERE ";
        $sql .= "s.table_schema = :table_schema ";
        $sql .= "AND s.table_name = :table_name ";
        $sql .= "AND s.index_name = :index_name ";
        $sql .= "AND s.index_name <> 'PRIMARY' ";
        $sql .= " ORDER BY s.seq_in_index ";
        
        $stmt = $this->connection->createStatement($sql);

        $stmt->setString(':table_schema', $tableSchema);
        $stmt->setString(':table_name', $tableName);
        $stmt->setString(':index_name', $indexName);

        $result = $stmt->execute();
        $indexArray = $result->toArray();

        return $indexArray;
    }

    /**
     * 制約を取得する
     *
     * @param string $tableSchema
     * @param string $tableName
     * @param string $constraintName
     * @return array
     */
    protected function getConstraint($tableSchema, $tableName, $constraintName)
    {
        $sql  = "SELECT t.constraint_name, t.constraint_type, k.table_name, k.column_name, k.ordinal_position ";
        $sql .= "FROM ";
        $sql .= "information_schema.table_constraints t ";
        $sql .= "JOIN ";
        $sql .= "information_schema.key_column_usage k ";
        $sql .= "ON t.constraint_name = k.constraint_name AND t.table_schema = k.table_schema AND t.table_name = k.table_name ";

        $sql .= "WHERE ";
        $sql .= "t.table_schema = :table_schema ";
        $sql .= "AND t.table_name = :table_name ";
        $sql .= "AND t.constraint_name = :constraint_name ";
        $sql .= "AND t.constraint_type <> 'PRIMARY KEY' ";
        $sql .= " ORDER BY k.ordinal_position ";
        
        $stmt = $this->connection->createStatement($sql);

        $stmt->setString(':table_schema', $tableSchema);
        $stmt->setString(':table_name', $tableName);
        $stmt->setString(':constraint_name', $constraintName);

        $result = $stmt->execute();
        $indexArray = $result->toArray();

        return $indexArray;
    }
}
