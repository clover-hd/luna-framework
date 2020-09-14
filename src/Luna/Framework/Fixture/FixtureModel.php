<?php

namespace Luna\Framework\Fixture;

use Luna\Framework\Database\ORM\Model;

class FixtureModel extends Model
{
    public function setTablename(string $tablename)
    {
        $this->tablename = $tablename;
    }
}