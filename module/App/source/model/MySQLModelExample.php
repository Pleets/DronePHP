<?php

namespace App\Model;

class MySQLModelExample extends \Pleets\Sql\AbstractionModel
{
    public function consulta()
    {
        $sql = "SELECT * FROM mysql.user";
        $result = $this->getDb()->query($sql);
        return $this->getDb()->getArrayResult();
    }
}