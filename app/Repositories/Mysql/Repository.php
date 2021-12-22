<?php

namespace App\Repositories\Mysql;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Connection;

class Repository
{
    protected $primaryKey;
    protected $table;

    public function __construct()
    {
        $this->primaryKey = 'id';
        $this->table = '';
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table($this->table);
    }

    /**
     * @return Expression
     */
    public function raw($str)
    {
        return $this->connection()->raw($str);
    }

    /**
     * @return Connection
     */
    public function connection()
    {
        return app('db')->connection('mysql');
    }
}
