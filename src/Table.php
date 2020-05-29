<?php

namespace Topdb;

class Table
{
    public $adpater = null;
    public static $data = array(
        'config' => [],
        'name' => 'catfan/medoo',
    );
    public static $names = array(
        'wuding/topdb' => 'Topdb',
        'catfan/medoo' => 'Medoo',
    );

    public function __construct($config = [], $name = null)
    {
        $this->inst();
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

    public function initAdpater($options = [], $config = [], $name = null)
    {
        $config = $config ? : self::$data['config'];
        $name = $name ? : self::$data['name'];
        $names = self::$names;
        if (array_key_exists($name, $names)) {
            $file = $names[$name];
            $class = "\\Topdb\\Adpater\\$file";
            $this->adpater = new $class($config, $options);
            $this->db = $this->adpater->database;
        }
    }

    public function inst()
    {
        $options = array(
            'db_name' => $this->db_name,
        );
        $this->initAdpater($options);
    }
}
