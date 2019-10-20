<?php

namespace Topdb;

class Table
{
    public $adpater = null;

    public function __construct($config = [], $name = 'catfan/medoo')
    {
        $names = array(
            'wuding/topdb' => 'Topdb',
            'catfan/medoo' => 'Medoo',
        );

        $options = array(
            'db_name' => $this->db_name,
        );

        if (array_key_exists($name, $names)) {
            $file = $names[$name];
            $class = "\\Topdb\\Adpater\\$file";
            $this->adpater = new $class($config, $options);
        }
    }

    public function query($sql)
    {
        return $this->adpater->query($sql);
    }
}
