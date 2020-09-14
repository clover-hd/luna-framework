<?php

namespace Luna\Framework\Controller;

use Luna\Framework\Database\ORM\Model;
use Luna\Framework\Http\Request;

trait DataTables
{
    /**
     * dataTablesからのリクエストをwhere条件に変換
     *
     * @param   $request    Request
     * @param   &$model     Model
     * @return  Model
     */
    protected function dataTablesFilter(Request $request, Model &$model, array $searchWithoutColumn = [])
    {
        $params = $request->getGet()->getRawVars();

        // WHERE句の生成
        $whereColumns = array();
        if (isset($params['search']) && isset($params['search']['value']) && $params['search']['value'] != '')
        {
            foreach ($params['columns'] as $col)
            {
                if ($col['searchable'] == "true" && $col['data'] != '')
                {
                    if (array_search($col['data'], $searchWithoutColumn) === false)
                    {
                        $whereColumns[] = " {$col['data']} LIKE :searchvalue ";
                    }
                }
            }
            if (count($whereColumns) > 0)
            {
                $where = implode(' OR ', $whereColumns);
                $model = $model->whereAnd(
                    $where,
                    [
                        'searchvalue' => "%{$params['search']['value']}%",
                    ]);
            }
        }

        // ORDER BY句の生成
        $orderBy = array();
        if (isset($params['order']) && count($params['order']) > 0)
        {
            foreach ($params['order'] as $order)
            {
                if (isset($params['columns'][$order['column']]) && $params['columns'][$order['column']]['data'] != '')
                {
                    $orderBy[] = " {$params['columns'][$order['column']]['data']} {$order['dir']} ";
                }
            }
            if (count($orderBy) > 0)
            {
                $model = $model->orderBy($orderBy);
            }
        }
        // オフセット
        if (isset($params['start']) && $params['start'] !== '') {
            $model->offset($params['start']);
        }
        if (isset($params['length']) && $params['length'] !== '') {
            $model->limit($params['length']);
        }

        return $model;
    }

    protected function getSearchColumns(Request $request)
    {
        $params = $request->getGet();
        $columns = array();
        if (isset($params['search']) && isset($params['search']['value']) && $params['search']['value'] != '')
        {
            foreach ($params['columns'] as $col)
            {
                if ($col['searchable'] == "true" && $col['data'] != '')
                {
                    $columns[] = $col['data'];
                }
            }
        }
        return $columns;
    }

    protected function getSearchValue(Request $request)
    {
        $params = $request->getGet();
        if (isset($params['search']) && isset($params['search']['value']) && $params['search']['value'] != '')
        {
            return $params['search']['value'];
        }
        return '';
    }

}
