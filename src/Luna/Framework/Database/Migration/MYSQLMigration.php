<?php

namespace Luna\Framework\Database\Migration;

use Luna\Framework\Database\Connection;

/**
 * mysql用のマイグレーション処理を行う
 */
class MYSQLMigration extends Migration
{
    /**
     * テーブルを作成する
     *
     * @param array $createYaml
     * @param Connection $connection
     * @return void
     */
    protected function createTable(array $createYaml, Connection $connection)
    {
        foreach ($createYaml as $tablename => $table) {
            echo "create table {$tablename} --- start\n";
            $sql = "CREATE TABLE {$tablename} ( ";
            $columnSqlList = [];
            foreach ($table['columns'] as $column) {
                $tmp = '';
                $dbNativeType = $this->getDBNativeType($column);
                $tmp = "`{$column['name']}` {$dbNativeType} ";

                if (isset($column['not_null']) && $column['not_null'] === false) {

                } else {
                    $tmp .= ' NOT NULL ';
                }
                if (isset($column['increment'])) {
                    $tmp .= ' AUTO_INCREMENT ';
                }
                if (isset($column['default'])) {
                    $default = \strtoupper($column['default']);
                    if ($default == 'NULL') {
                        $tmp .= " DEFAULT {$column['default']} ";
                    } else if ($default == 'CURRENT_TIMESTAMP') {
                        $tmp .= " DEFAULT {$column['default']}() ";
                    } else {
                        $tmp .= " DEFAULT " . $connection->escapedString($column['default']) . " ";
                    }

                }
                if (isset($column['on_update'])) {
                    $onUpdate = \strtoupper($column['on_update']);
                    if ($onUpdate == 'NULL') {
                        $tmp .= " ON UPDATE {$column['on_update']} ";
                    } else if ($onUpdate == 'CURRENT_TIMESTAMP') {
                        $tmp .= " ON UPDATE {$column['on_update']}() ";
                    } else {
                        $tmp .= " ON UPDATE " . $connection->escapedString($column['on_update']) . "' ";
                    }
                }
                if (isset($column['comment'])) {
                    $tmp .= " COMMENT " . $connection->escapedString($column['comment']) . " ";
                }
                $columnSqlList[] = $tmp;
            }

            if (isset($table['primary_key']) && \is_array($table['primary_key'])) {
                $primaryKeyList = [];
                foreach ($table['primary_key'] as $key) {
                    if (empty($key) === false) {
                        $primaryKeyList[] = "`{$key}`";
                    }
                }
                if (count($primaryKeyList) > 0) {
                    $primaryKey = implode(',', $primaryKeyList);
                    $columnSqlList[] = " PRIMARY KEY ({$primaryKey}) ";
                }
            }

            if (isset($table['index']) && \is_array($table['index'])) {
                foreach ($table['index'] as $index) {
                    if (empty($index['name']) === false) {
                        if (empty($index['type']) === false) {
                            $tmp = "{$index['type']} INDEX ";
                        } else {
                            $tmp = 'INDEX';
                        }
                        $tmp .= " `{$index['name']}` ";
                        $columnList = [];
                        foreach ($index['columns'] as $col) {
                            $columnList[] = "`{$col}`";
                        }
                        $tmp .= ' ( ' . \implode(',', $columnList) . ' ) ';
                        $columnSqlList[] = $tmp;
                    }
                }
            }

            if (isset($table['engine']) && empty($table['engine']) === false) {
                $dbEngine = $table['engine'];
            } else {
                $dbEngine = 'InnoDB';
            }
            if (isset($table['charater_set']) && empty($table['charater_set']) === false) {
                $characterSet = $table['charater_set'];
            } else {
                $characterSet = 'utf8mb4';
            }
            if (isset($table['collate']) && empty($table['collate']) === false) {
                $collate = $table['collate'];
            } else {
                $collate = 'utf8mb4_general_ci';
            }

            $sql .= implode(',', $columnSqlList);
            $sql .= " ) ENGINE={$dbEngine} CHARACTER SET {$characterSet} COLLATE {$collate} ";
            echo "{$sql}\n";
            $connection->exec($sql);
            echo "create table {$tablename} --- complete\n";
        }
    }

    /**
     * ビューを作成する
     *
     * @param array $createYaml
     * @param Connection $connection
     * @return void
     */
    protected function createView(array $createYaml, Connection $connection)
    {
        foreach ($createYaml as $viewname => $view) {
            echo "create view {$viewname} --- start\n";
            $sql = "CREATE ";
            if (isset($view['algorithm']) && empty($view['algorithm']) === false) {
                if (\strtolower($view['algorithm']) == 'undefined') {
                    $sql .= " ALGORITHM = UNDEFINED ";
                } else if (\strtolower($view['algorithm']) == 'merge') {
                    $sql .= " ALGORITHM = MERGE ";
                } else if (\strtolower($view['algorithm']) == 'temptable') {
                    $sql .= " ALGORITHM = TEMPTABLE ";
                } else {
                    $sql .= " ALGORITHM = '{$view['algorithm']} ";
                }
            }
            if (isset($view['definer']) && empty($view['definer']) === false) {
                if (\strtolower($view['definer']) == 'current_user') {
                    $sql .= " DEFINER = CURRENT_USER ";
                } else {
                    $sql .= " DEFINER = '{$view['definer']}' ";
                }
            }
            if (isset($view['sql_security']) && empty($view['sql_security']) === false) {
                if (\strtolower($view['sql_security']) == 'definer') {
                    $sql .= " SQL SECURITY DEFINER ";
                } else if (\strtolower($view['sql_security']) == 'definer') {
                    $sql .= " SQL SECURITY DEFINER ";
                } else {
                    $sql .= " SQL SECURITY '{$view['sql_security']}' ";
                }
            }
            $sql .= " VIEW {$viewname} AS {$view['select_statement']} ";
            echo "{$sql}\n";
            $connection->exec($sql);
            echo "create view {$viewname} --- complete\n";
        }
    }

    /**
     * テーブルを変更する
     *
     * @param array $changeYaml
     * @param Connection $connection
     * @return void
     */
    protected function changeTable(array $changeYaml, Connection $connection)
    {
        foreach ($changeYaml as $tablename => $table) {
            echo "change table {$tablename} --- start\n";
            // カラム
            if (isset($table['columns']) && is_array($table['columns'])) {
                foreach ($table['columns'] as $column) {
                    $oldColumnName = "{$column['name']}";
                    if (isset($column['name_to']) && empty($column['name_to']) === false) {
                        $newColumnName = "{$column['name_to']}";
                    } else {
                        $newColumnName = $oldColumnName;
                    }
                    $dbNativeType = $this->getDBNativeType($column);
                    $sql = "ALTER TABLE `{$tablename}` CHANGE `{$oldColumnName}` `{$newColumnName}` {$dbNativeType} ";

                    if (isset($column['not_null']) && $column['not_null'] === false) {

                    } else {
                        $sql .= ' NOT NULL ';
                    }
                    if (isset($column['increment'])) {
                        $sql .= ' AUTO_INCREMENT ';
                    }
                    if (isset($column['default'])) {
                        $default = \strtoupper($column['default']);
                        if ($default == 'NULL') {
                            $sql .= " DEFAULT {$column['default']} ";
                        } else if ($default == 'CURRENT_TIMESTAMP') {
                            $sql .= " DEFAULT {$column['default']}() ";
                        } else {
                            $sql .= " DEFAULT " . $connection->escapedString($column['default']) . " ";
                        }

                    }
                    if (isset($column['on_update'])) {
                        $onUpdate = \strtoupper($column['on_update']);
                        if ($onUpdate == 'NULL') {
                            $sql .= " ON UPDATE {$column['on_update']} ";
                        } else if ($onUpdate == 'CURRENT_TIMESTAMP') {
                            $sql .= " ON UPDATE {$column['on_update']}() ";
                        } else {
                            $sql .= " ON UPDATE " . $connection->escapedString($column['on_update']) . " ";
                        }
                    }
                    echo "{$sql}\n";
                    $connection->exec($sql);
                }
            }

            // 主キー
            if (isset($table['primary_key']) && \is_array($table['primary_key'])) {
                $primaryKeyList = [];
                foreach ($table['primary_key'] as $key) {
                    if (empty($key) === false) {
                        $primaryKeyList[] = "`{$key}`";
                    }
                }
                if (count($primaryKeyList) > 0) {
                    $primaryKey = implode(',', $primaryKeyList);
                    echo "ALTER TABLE {$tablename} DROP INDEX IF EXISTS `PRIMARY`, ADD PRIMARY KEY ({$primaryKey})\n";
                    $connection->exec("ALTER TABLE {$tablename} DROP INDEX IF EXISTS `PRIMARY`, ADD PRIMARY KEY ({$primaryKey})");
                }
            }
            // インデックス
            if (isset($table['index']) && \is_array($table['index'])) {
                foreach ($table['index'] as $index) {
                    if (empty($index['name']) === false) {
                        if (isset($index['drop']) && $index['drop'] === true) {
                            echo "DROP INDEX {$index['name']} ON {$tablename}\n";
                            $connection->exec("DROP INDEX {$index['name']} ON {$tablename}");
                        } else {
                            if (empty($index['type']) === false) {
                                $type = "{$index['type']} ";
                            } else {
                                $type = ' ';
                            }
                            $columnList = [];
                            foreach ($index['columns'] as $col) {
                                $columnList[] = "`{$col}`";
                            }
                            $columns = \implode(',', $columnList);
                            echo "CREATE {$type} INDEX {$index['name']} ON {$tablename} ( {$columns} )\n";
                            $connection->exec("CREATE {$type} INDEX {$index['name']} ON {$tablename} ( {$columns} )");
                        }
                    }
                }
            }

            // テーブル名
            if (isset($table['name_to']) && empty($table['name_to']) === false) {
                echo "ALTER TABLE `{$tablename}` RENAME TO `{$table['name_to']}`\n";
                $connection->exec("ALTER TABLE `{$tablename}` RENAME TO `{$table['name_to']}`");
            }
            // テーブルコメント
            if (isset($table['comment']) && empty($table['comment']) === false) {
                $exec_sql = "ALTER TABLE `{$tablename}` COMMENT '" . $table['comment'] . "'";
                echo $exec_sql . "\n";
                $connection->exec($exec_sql);
            }
            echo "change table {$tablename} --- complete\n";
        }
    }

    /**
     * ビューを変更する
     *
     * @param array $createYaml
     * @param Connection $connection
     * @return void
     */
    protected function changeView(array $changeYaml, Connection $connection)
    {
        foreach ($changeYaml as $viewname => $view) {
            echo "create view {$viewname} --- start\n";
            $sql = "ALTER ";
            if (isset($view['algorithm']) && empty($view['algorithm']) === false) {
                if (\strtolower($view['algorithm']) == 'undefined') {
                    $sql .= " ALGORITHM = UNDEFINED ";
                } else if (\strtolower($view['algorithm']) == 'merge') {
                    $sql .= " ALGORITHM = MERGE ";
                } else if (\strtolower($view['algorithm']) == 'temptable') {
                    $sql .= " ALGORITHM = TEMPTABLE ";
                } else {
                    $sql .= " ALGORITHM = '{$view['algorithm']} ";
                }
            }
            if (isset($view['definer']) && empty($view['definer']) === false) {
                if (\strtolower($view['definer']) == 'current_user') {
                    $sql .= " DEFINER = CURRENT_USER ";
                } else {
                    $sql .= " DEFINER = '{$view['definer']}' ";
                }
            }
            if (isset($view['sql_security']) && empty($view['sql_security']) === false) {
                if (\strtolower($view['sql_security']) == 'definer') {
                    $sql .= " SQL SECURITY DEFINER ";
                } else if (\strtolower($view['sql_security']) == 'definer') {
                    $sql .= " SQL SECURITY DEFINER ";
                } else {
                    $sql .= " SQL SECURITY '{$view['sql_security']}' ";
                }
            }
            $sql .= " VIEW {$viewname} AS {$view['select_statement']} ";
            echo "{$sql}\n";
            $connection->exec($sql);
            echo "create view {$viewname} --- complete\n";
        }
    }

    /**
     * カラムを追加する
     *
     * @param array $changeYaml
     * @param Connection $connection
     * @return void
     */
    protected function addColumn(array $addYaml, Connection $connection)
    {
        foreach ($addYaml as $tablename => $columns) {
            $sql = "ALTER TABLE `{$tablename}` ADD COLUMN ";
            $columnSqlList = [];
            foreach ($columns as $column) {
                $tmp = '';
                $dbNativeType = $this->getDBNativeType($column);
                $tmp = "`{$column['name']}` {$dbNativeType} ";

                if (isset($column['not_null']) && $column['not_null'] === false) {

                } else {
                    $tmp .= ' NOT NULL ';
                }
                if (isset($column['increment'])) {
                    $tmp .= ' AUTO_INCREMENT ';
                }
                if (isset($column['default'])) {
                    $default = \strtoupper($column['default']);
                    if ($default == 'NULL') {
                        $tmp .= " DEFAULT {$column['default']} ";
                    } else if ($default == 'CURRENT_TIMESTAMP') {
                        $tmp .= " DEFAULT {$column['default']}() ";
                    } else {
                        $tmp .= " DEFAULT " . $connection->escapedString($column['default']) . " ";
                    }

                }
                if (isset($column['on_update'])) {
                    $onUpdate = \strtoupper($column['on_update']);
                    if ($onUpdate == 'NULL') {
                        $tmp .= " ON UPDATE {$column['on_update']} ";
                    } else if ($onUpdate == 'CURRENT_TIMESTAMP') {
                        $tmp .= " ON UPDATE {$column['on_update']}() ";
                    } else {
                        $tmp .= " ON UPDATE " . $connection->escapedString($column['on_update']) . "' ";
                    }
                }
                if (isset($column['comment'])) {
                    $tmp .= " COMMENT " . $connection->escapedString($column['comment']) . " ";
                }
                if (isset($column['first']) && $column['first'] === true) {
                    $tmp .= " FIRST ";
                } else if (isset($column['after']) && empty($column['after']) === false) {
                    $tmp .= " AFTER {$column['after']} ";
                } else if (isset($column['before']) && empty($column['before']) === false) {
                    $tmp .= " BEFORE {$column['before']} ";
                }
                if (empty($tmp) === false) {
                    $columnSqlList[] = " ADD COLUMN " . $tmp;
                }
            }
            if (count($columnSqlList) > 0) {
                $sql = "ALTER TABLE `{$tablename}` " . implode(',', $columnSqlList);
                echo "$sql\n";
                $connection->exec($sql);

            }
        }
    }

    protected function dropTable(array $dropTableYaml, Connection $connection)
    {
        foreach ($dropTableYaml as $dropTable) {
            $sql = "DROP TABLE IF EXISTS `{$dropTable}`";
            echo "$sql\n";
            $connection->exec($sql);
        }
    }

    protected function dropView(array $dropViewYaml, Connection $connection)
    {
        foreach ($dropViewYaml as $dropView) {
            $sql = "DROP VIEW IF EXISTS `{$dropView}`";
            echo "$sql\n";
            $connection->exec($sql);
        }
    }

    protected function dropColumn(array $dropColumnYaml, Connection $connection)
    {
        foreach ($dropColumnYaml as $tablename => $columns) {
            $columnSqlList = [];
            foreach ($columns as $column) {
                $columnSqlList[] = " DROP COLUMN `{$column}` ";
            }
            $sql = "ALTER TABLE `{$tablename}` " . \implode(',', $columnSqlList);
            echo "$sql\n";
            $connection->exec($sql);
        }
    }

    /**
     * カラム情報からDB無いてぃぶの型定義文字列を返す
     *
     * @param array $column
     * @return string
     */
    protected function getDBNativeType($column)
    {
        $typeStr = '';
        $type = isset($column['type_to']) ? $column['type_to'] : $column['type'];
        $size = isset($column['size_to']) ? $column['size_to'] : (isset($column['size']) ? $column['size'] : '');
        switch ($type) {
            case 'int':
            case 'integer':
                $typeStr = " int ";
                break;
            case 'tinyint':
                $typeStr = " tinyint ";
                break;
            case 'bigint':
                $typeStr = " bigint ";
                break;
            case 'decimal':
                $typeStr = " decimal({$size}) ";
                break;
            case 'float':
                $typeStr = " float ";
                break;
            case 'double':
                $typeStr = " double ";
                break;
            case 'varchar':
                $typeStr = " varchar({$size}) ";
                break;
            case 'text':
                $typeStr = " text ";
                break;
            case 'char':
                $typeStr = " char({$size}) ";
                break;
            case 'time':
                $typeStr = " time ";
                break;
            case 'date':
                $typeStr = " date ";
                break;
            case 'datetime':
                $typeStr = " datetime ";
                break;
            case 'text':
                $typeStr = " text ";
                break;
            case 'geometry':
                $typeStr = " geometry ";
        }
        return $typeStr;
    }
}
