<?php

namespace Topdb\Adapter;

use Medoo\Medoo as Medo;

class Medoo extends _Abstract
{
    public $config = array(
        'database_type' => 'mysql',
        'database_name' => 'mysql',
        'server' => 'localhost',
        'username' => '',
        'password' => '',
        'port' => '3306',
    );

    public function __construct($config, $options)
    {
        $config = $this->setConfig($config, $options);
        $this->database = new Medo($config);
    }

    public function setConfig($config, $options)
    {
        $arr = array_merge($this->config, $config);
        if (isset($options['db_name']) && $options['db_name']) {
            $arr['database_name'] = $options['db_name'];
        }
        $arr = $this->array_keys_clean($arr);
        return $arr;
    }

    public function array_keys_clean($arr)
    {
        $item = array('database_type', 'database_name', 'server', 'username', 'password', 'port');
        foreach ($arr as $key => $value) {
            if (!in_array($key, $item)) {
                unset($arr[$key]);
            }
        }
        return $arr;
    }

    public function exec($statement)
    {
        return $this->database->pdo->exec($statement);
    }
}
