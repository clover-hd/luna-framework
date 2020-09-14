<?php

namespace Luna\Framework\Database\ORM;

use Luna\Framework\Database\Type\Geometry;

class SQLBuilder
{

    public static function select(Model $model)
    {
        $sql = SQLBuilder::selectBuilder($model)
        . SQLBuilder::fromBuilder($model)
        . SQLBuilder::joinBuilder($model)
        . SQLBuilder::whereBulder($model)
        . SQLBuilder::groupByBuilder($model)
        . SQLBuilder::orderByBuilder($model)
        . SQLBuilder::limitOffsetBuilder($model);

        $sql = SQLBuilder::replaceTablename($sql, $model);

        $stmt = $model->getConnection()->createStatement($sql, $model->getWhereParams());
        return $stmt->getExecSql();
    }

    public static function update(Model $model)
    {
        $sql = SQLBuilder::updateBuilder($model)
        . SQLBuilder::whereBulder($model);

        $sql = SQLBuilder::replaceTablename($sql, $model);
        
        $stmt = $model->getConnection()->createStatement($sql, $model->getWhereParams());
        return $stmt->getExecSql();
    }

    public static function insert(Model $model)
    {
        $sql = SQLBuilder::insertBuilder($model);

        $sql = SQLBuilder::replaceTablename($sql, $model);
        
        $stmt = $model->getConnection()->createStatement($sql, $model->getWhereParams());
        return $stmt->getExecSql();
    }

    public static function delete(Model $model)
    {
        $sql = SQLBuilder::deleteBuilder($model)
        . SQLBuilder::whereBulder($model);

        $sql = SQLBuilder::replaceTablename($sql, $model);

        $stmt = $model->getConnection()->createStatement($sql, $model->getWhereParams());
        return $stmt->getExecSql();
    }

    public static function selectBuilder(Model $model)
    {
        $sql = 'SELECT ';
        if ($model->getDistinct()) {
            $sql .= 'DISTINCT ';
        }
        return $sql . implode(',', $model->getSelect()) . " ";
    }

    public static function fromBuilder(Model $model)
    {
        $fromModel = $model->getFromModel();
        if ($model->getFromModel() != null)
        {
            return ' FROM ( ' . SQLBuilder::select($fromModel['model']) . " ) AS {$fromModel['as']}";
        } else if (empty($model->getAs()) === false) {
            return " FROM " . $model->getTablename() . " AS " . $model->getAs();
        } else {
            return " FROM " . $model->getTablenameWithAs() . " ";
        }
    }

    public static function joinBuilder(Model $model)
    {
        $sql = '';
        $joinModels = $model->getJoinModels();
        if (is_array($joinModels) && count($joinModels) > 0)
        {
            foreach ($joinModels as $joinModel) {
                $sql .= " {$joinModel['type']} (" . SQLBuilder::select($joinModel['model']) . ") AS {$joinModel['as']}";
                $sql .= " ON ";
                $sql .= "{$joinModel['on']} ";
            }
        }
        return $sql;
    }

    public static function updateBuilder(Model $model)
    {
        if (is_array($model->getTablename())) {} else {
            $sql = " UPDATE " . $model->getTablename() . " SET ";
            $updateColumns = array();
            $updateVals = array();
            foreach ($model->getUpdateColumns() as $col => $val) {
                $updateColumns[] = " {$col} = :{$col} ";
                $updateVals[$col] = $val;
            }

            return $sql . implode(',', $updateColumns);
        }
    }

    public static function insertBuilder(Model $model)
    {
        if (is_array($model->getTablename())) {} else {
            $sql = " INSERT INTO " . $model->getTablename() . " ";
            $updateColumnLists = array();
            $updateValLists = array();
            $updateVals = array();
            $customSequanceQuery = $model->getCustomSequanceQuery();
            if (empty($customSequanceQuery) === false) {
                // インサートする値に
                if (array_key_exists($model->getCustomSequanceField(), $model->getInsertColumns()) === false) {
                    $updateColumnLists[] = $model->getCustomSequanceField();
                    $updateValLists[] = "({$customSequanceQuery})";
                }
            }
            foreach ($model->getInsertColumns() as $col => $val) {
                $updateColumnLists[] = " {$col} ";
                $updateValLists[] = " :{$col} ";
                $updateVals[$col] = $val;
            }
            $sql .= " ( " . implode(',', $updateColumnLists) . " ) ";
            $sql .= " VALUES  ";
            $sql .= " ( " . implode(',', $updateValLists) . " ) ";

            return $sql;
        }
    }

    public static function deleteBuilder(Model $model)
    {
        if (is_array($model->getTablename())) {} else {
            $sql = " DELETE FROM " . $model->getTablename() . " ";
            return $sql;
        }
    }

    public static function whereBulder(Model $model)
    {
        $where = $model->getWhere();
        if (is_null($where) || count($where) == 0 || $where['where'] == '') {
            return ' ';
        } else {
            // WHERE区内の配列、Modelを探す
            foreach ($where['params'] as $col => $val) {
                if (is_array($val)) {
                    // IN句のパラメータを展開(:name -> :name_1, :name_2, ...)
                    $inParams = [];
                    foreach ($val as $idx => $inVal) {
                        $inParams[] = ":{$col}_{$idx}";
                        $where['params']["{$col}_{$idx}"] = $inVal;
                    }
                    $where['where'] = mb_ereg_replace(":{$col}", implode(',', $inParams), $where['where']);
                    unset($where['params'][$col]);
                } else if ($val instanceof Model) {
                    $sql = SQLBuilder::select($val);
                    $where['where'] = mb_ereg_replace(":{$col}", "({$sql})" , $where['where']);
                    unset($where['params'][$col]);
                }
                $model->setWhere($where);
            }
            return " WHERE " . $where['where'];
        }
    }

    public static function orderByBuilder(Model $model)
    {
        $orders = $model->getOrderBys();
        if (is_null($orders) || count($orders) == 0) {
            return ' ';
        } else {
            return " ORDER BY " . implode(',', $orders);
        }
    }

    public static function groupByBuilder(Model $model)
    {
        $orders = $model->getGroupBys();
        if (is_null($orders) || count($orders) == 0) {
            return ' ';
        } else {
            return " GROUP BY " . implode(',', $orders);
        }
    }

    public static function limitOffsetBuilder(Model $model)
    {
        $limit = $model->getLimit();
        $offset = $model->getOffset();
        if (is_null($limit) && is_null($offset)) {
            return ' ';
        } else if (is_null($limit) && is_null($offset) === false) {
            return " LIMIT " . intval($offset);
        } else if (is_null($limit) === false && is_null($offset)) {
            return " LIMIT 0, " . intval($limit);
        } else if (is_null($limit) === false && is_null($offset) === false) {
            return " LIMIT " . intval($offset) . ", " . intval($limit);
        }
        return ' ';
    }

    public static function replaceTablename(string $sql, Model $model)
    {
        return preg_replace('/__TABLENAME__/u', $model->getTablenameWithAs(), $sql);
    }
}
