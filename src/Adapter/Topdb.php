<?php

namespace Topdb\Adapter;

use Ext\PhpPdoMysql;

class Topdb extends _Abstract
{
    const VERSION = '20.2023.12.1';

    public $config = array(
        'db_type' => 'mysql',
        'dbname' => 'mysql',
        'host' => 'localhost',
        'username' => '',
        'password' => '',
        'port' => '3306',
    );

    public $ignore_types = [];
    public $ignore_null = [
        'string' => null,
        'array' => null,
        'object' => null,
        'integer' => null,
        'double' => null,
        'NULL' => null,
        'boolean' => null,
    ];
    public $ignore_values = [];
    public $ignore_fields = [];
    public $fields = '';
    public $keys_clean = null;

    /*
    +---------------------------------------
    + 基本
    +---------------------------------------
    */

    public function __construct($config, $options)
    {
        $conf = $this->setConfig($config, $options);
        $this->database = new PhpPdoMysql($conf);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->database, $name), $arguments);
    }

    public function setConfig($config, $options)
    {
        $conf = array_merge($this->config, $config);
        $opt = array_merge($conf, $options);
        $this->setVars($opt);
        $arr = $this->keys_clean ? $this->array_keys_clean($opt) : $opt;
        return $arr;
    }

    /*
    +---------------------------------------
    + CRUD
    +---------------------------------------
    */

    public function insert($data = [])
    {
        $pieces = array();
        $ins = null;
        foreach ($data as $key => $value) {
            $type = gettype($value);
            $quote = 1;

            // 自定义值
            if (is_numeric($key)) {
                $pieces[] = $value;
                continue 1;

            } elseif ($this->ignore_fields) { // 忽略字段名
                $fields = is_array($this->ignore_fields) ? $this->ignore_fields : explode(',', $this->ignore_fields);
                if (in_array($key, $fields)) {
                    continue 1;
                }

            } elseif ($this->fields) { // 忽略非字段名
                $fields = is_array($this->fields) ? $this->fields : explode(',', $this->fields);
                if (!in_array($key, $fields)) {
                    continue 1;
                }

            } elseif (!$key) { // 忽略空字段名
                continue 1;
            }

            // 要忽略的值
            if (in_array($value, $this->ignore_values)) {
                continue 1;
            }

            // 忽略类型
            if (in_array($type, $this->ignore_types)) {
                continue 1;
            }

            // 忽略类型空值
            if ($this->ignore_null[$type] && !$value) {
                continue 1;
            }

            if ('string' == $type) {
                if ($this->ignore_null[$type] && !trim($value)) {
                    continue 1;
                }
                $value = addslashes($value);

            } elseif (in_array($type, ['array', 'object'])) {
                $value = json_encode($value);
                $value = addslashes($value);

            } elseif (in_array($type, ['integer', 'double'])) {
                $quote = 0;

            } elseif (in_array($type, ['bool', 'boolean'])) {
                $quote = 0;
                $value = intval($value);

            } elseif ('NULL' == $type) {
                $quote = 0;
                $value = $type;

            } else {
                print_r(["type is $type", $value, __FILE__, __LINE__]);
                exit;
            }

            if ($quote) {
                $value = "'$value'";
            }
            $pieces[] = "`$key` = $value";
        }

        if ($pieces) {
            $set = implode(', ', $pieces);
            $sql = "INSERT INTO $this->table_name SET $set";
            $ins = $this->database->insert($sql);
        }
        return $ins;
    }

    /*
    +---------------------------------------
    + 补充
    +---------------------------------------
    */

    public function array_keys_clean($arr)
    {
        $item = array('host', 'username', 'password', 'port', 'dbname');
        foreach ($arr as $key => $value) {
            if (!in_array($key, $item)) {
                unset($arr[$key]);
            }
        }
        return $arr;
    }
}
