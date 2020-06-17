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
        $conf = $this->setConfig($config, $options);
        $this->database = new Db($conf);
    }

    public function setConfig($config, $options)
    {
        $conf = array_merge($this->config, $config);
        $opt = array_merge($conf, $options);
        $arr = $this->array_keys_clean($opt);
        return $arr;
    }

    public function setVars($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function array_keys_clean($arr)
    {
        $item = array('host', 'username', 'password', 'port', 'db_name');
        foreach ($arr as $key => $value) {
            if (!in_array($key, $item)) {
                unset($arr[$key]);
            }
        }
        return $arr;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->database, $name), $arguments);
    }

    public function insert($data = [])
    {
        $pieces = array();
        $ins = null;
        foreach ($data as $key => $value) {
            // 自定义值
            if (is_numeric($key)) {
                $pieces[] = $value;
                continue 1;
            } elseif ($this->fields) { // 忽略非字段名
                $fields = explode(',', $this->fields);
                if (!in_array($key, $fields)) {
                    continue 1;
                }
            }

            // 键值对，忽略空值
            $vt = trim($value);
            if ($vt || !is_string($vt)) {
                $vs = addslashes($vt);
                $pieces[] = "$key = '$vs'";
            }
        }

        if ($pieces) {
            $set = implode(', ', $pieces);
            $sql = "INSERT INTO $this->table_name SET $set";
            $ins = $this->database->insert($sql);
        }
        return $ins;
    }
}
