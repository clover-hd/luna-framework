<?php

namespace Luna\Framework\Database\ORM;

use Iterator;
use Luna\Framework\Database\Exception\RecordNotFoundException;

class ResultSet implements Iterator
{
    protected $datasourceName = 'default';
    protected $modelClassName = '';

    protected $position = 0;

    protected function attachModel(array $values)
    {
        $model = call_user_func("{$this->modelClassName}::instance", $this->datasourceName);
        $model->attachValues($values);
        return $model;
    }

    public function setModel(string $modelClassName = '', string $datasourceName = 'default')
    {
        $this->modelClassName = $modelClassName;
        $this->datasourceName = $datasourceName;
    }

    public function current()
    {
        return null;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return false;
    }

    public function count()
    {
        return 0;
    }

    public function close()
    {
    }

    /**
     * 結果セットの最初の要素を返す
     *
     * @return mixed
     */
    public function getFirst()
    {
        throw new RecordNotFoundException();
        return new Model();
    }

    public function toArray()
    {
        $resultArray = array();
        $this->rewind();
        foreach ($this as $record)
        {
            $resultArray[] = $record->toArray();
        }
        return $resultArray;
    }

    public function toRawArray()
    {
        $resultArray = array();
        return $resultArray;
    }
}
