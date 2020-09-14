<?php

namespace Luna\Framework\Database\Migration\Model;

use Luna\Framework\Database\ORM\Model;

class DBMigration extends Model
{
    protected $deleteFlagField = '';
    
    public function getTablename()
    {
        return 'db_migrations';
    }
}