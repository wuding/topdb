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

        if (array_key_exists($name, $names)) {
            $file = $names[$name];
            $class = "Topdb\\Adapter\\$file";
            $this->adpater = new $class($config);
        }
    }

    public function query($sql)
    {
        return $this->adapter->query($sql);
    }
}
