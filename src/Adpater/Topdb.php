<?php

namespace Topdb\Adpater;

use Ext\PhpPdoMysql as Db;

class Topdb extends _Abstract
{
    public $config = array(
        'db_type' => 'mysql',
        'db_name' => 'mysql',
        'host' => 'localhost',
        'username' => '',
        'password' => '',
        'port' => '3306',
    );

    public function __construct($config, $options)
    {
        $config = $this->setConfig($config, $options);
        $this->database = new Db($config);
    }

    public function setConfig($config, $options)
    {
        $arr = array_merge($this->config, $config);
        $arr = $this->array_keys_clean($arr);
        return $arr;
    }

    public function array_keys_clean($arr)
    {
        $item = array('host', 'username', 'password', 'port');
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
