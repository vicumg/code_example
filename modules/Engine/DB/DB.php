<?php


namespace App\Engine\DB;


class DB
{
    protected $connection;

    public $lastId = '';

    public function __construct()
    {

        include __DIR__.'/../../../db_connect.php';

        $this->connection = $link;

    }

    public function query($sql)
    {

        $query = mysqli_query($this->connection,$sql);
        if (!$this->connection->errno) {
            if ($query instanceof \mysqli_result) {
                $data = array();

                while ($row = $query->fetch_assoc()) {
                    $data[] = $row;
                }

                $result = new \stdClass();
                $result->num_rows = $query->num_rows;
                $result->row = isset($data[0]) ? $data[0] : array();
                $result->rows = $data;


                $query->close();



                return $result;
            } else {
                $this->lastId = $this->connection->insert_id;
                return true;
            }
        } else {
            throw new \Exception('Error: ' . $this->connection->error  . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
        }

    }
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
}