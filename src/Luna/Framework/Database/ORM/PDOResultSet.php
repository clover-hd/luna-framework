<?php

namespace Luna\Framework\Database\ORM;

use Luna\Framework\Database\Exception\RecordNotFoundException;
use PDO;
use PDOStatement;

class PDOResultSet extends ResultSet
{
    protected $pdoStmt;
    private $currentValue;

    private $records;

    public function __construct(PDOStatement $pdoStmt)
    {
        $this->pdoStmt = $pdoStmt;
        $this->position = -1;
        $this->records = $pdoStmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->pdoStmt->closeCursor();
    }

    public function current()
    {
        return $this->currentValue;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        // $val = $this->pdoStmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
        // $val = $this->pdoStmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $this->position++);
        if ($this->position < 0) {
            $val = \reset($this->records);
        } else {
            $val = next($this->records);
        }
        $this->position++;
        // next($this->records);
        if ($val !== false)
        {
            $this->currentValue = $this->attachModel($val);
        } else {
            $this->currentValue = $val;
        }
    }

    public function rewind()
    {
        if (count($this->records) > 0) {
            $this->position = 0;
            // $val = $this->pdoStmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, 0);
            \reset($this->records);
            $val = $this->records[$this->position];
            if ($val !== false)
            {
                $this->currentValue = $this->attachModel($val);
            } else {
                $this->currentValue = $val;
            }
        }
    }

    public function valid()
    {
        $val = current($this->records);
        if ($val !== false)
        {
            $this->currentValue = $this->attachModel($val);
        } else {
            $this->currentValue = $val;
        }
        return $this->currentValue !== false;
    }

    public function count()
    {
        //    return $this->pdoStmt->rowCount();
        return count($this->records);
    }

    public function getFirst(): Model
    {
        if ($this->count() == 0) {
            throw new RecordNotFoundException();
        }
        $this->rewind();
        return $this->current();
    }

    public function close()
    {
        //    $this->pdoStmt->closeCursor();
        $this->records = [];
    }

    public function toArray()
    {
        return $this->records;
    }


    public function toRawArray()
    {
        // return $this->pdoStmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->records;
    }
}
