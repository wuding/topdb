<?php

namespace Topdb;

class Table
{
    public $adpater = null;
    public static $data = array(
        'config' => [],
        'name' => 'catfan/medoo',
    );

    public function __construct($config = [], $name = null)
    {
        $config = $config ? : self::$data['config'];
        $name = $name ? : self::$data['name'];
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

    public function exec($statement)
    {
        return $this->adpater->exec($statement);
    }

    public static function init($config = [], $name = null)
    {
        if ($config) {
            self::$data['config'] = $config;
        }

        if ($name) {
            self::$data['name'] = $name;
        }
    }
}
