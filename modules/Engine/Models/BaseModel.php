<?php


namespace App\Engine\Models;


use App\Engine\DB\DB;

class BaseModel
{

    protected $db;
    protected $table;

    public function __construct()
    {

        $this->db = new DB();

    }

}