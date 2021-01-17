<?php

namespace Topdb;

use Ext\PDObj;
use Pkg\Glob;

class Tbl
{
    // 配置
    public static $vars = null;
    public $config_item = null;
    public $db_connect = null;
    public $db_name = null;
    public $table_name = null;
    public $primary_key = null;
    public $data = array(
        'mysql' => array(
            '' => 'host,port,dbname,charset',
            'host' => '127.0.0.1',
            'port' => 3306,
        ),
    );

    // 运行时
    public static $connects = array();
    public $key = null;

    public function __construct($vars = null)
    {
        if (null !== $vars) {
            self::$vars = $vars;
        }
        $this->connect($vars);
    }

    public function __call($name, $arguments)
    {
        $obj = self::$connects[$this->key];
        return call_user_func_array(array($obj, $name), $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        $obj = new static();
        return call_user_func_array(array($obj, $name), $arguments);
    }

    public function connect($vars = null)
    {
        $conf = $this->conf($vars);
        $dsn = $this->dsn($conf);
        $username = $conf['username'] ?? null;
        $password = $conf['password'] ?? null;
        $options = $conf['options'] ?? null;
        $arr = array(
            'dsn' => $dsn,
            'username' => $username,
            'passwd' => $password,
            'options' => $options,
        );
        $json = json_encode($arr);
        $this->key = $key = md5($json);
        if (array_key_exists($key, self::$connects)) {
            $conn = self::$connects[$key];
            return $conn;
        }
        self::$connects[$key] = $conn = new PDObj($dsn, $username, $password, $options);
        return $conn;
    }

    public function conf($vars = null)
    {
        $vars = null === $vars ? array() : $vars;
        $conf = Glob::cnf($this->db_connect, 'database');
        $conf = array_merge($conf, $vars);
        $type = ($conf['db_type'] ?? null) ?: 'mysql';
        $data = $this->data[$type] ?: array();
        $data = array_merge($data, $conf);
        $data['dsn_prefix'] = ($data['dsn_prefix'] ?? null) ?: $type;
        return $data;
    }

    public function dsn($variable = null)
    {
        $prefix = $variable['dsn_prefix'] ?: '';
        $string = $variable[''] ?? array();
        $allow = explode(',', $string);
        // 参数组
        $pieces = array();
        foreach ($variable as $key => $value) {
            if ($allow && !in_array($key, $allow)) {
                continue 1;
            }
            // 键值对
            $fragment = '';
            if (!is_numeric($key) && $key) {
                $fragment .= "$key=";
            }
            $fragment .= $value;
            $pieces[] = $fragment;
        }
        $str = implode(';', $pieces);
        $dsn = "$prefix:$str";
        return $dsn;
    }

    /*
    拼接
    */
    public static function columnName($variable = null)
    {
        if (is_object($variable)) {
            $column = $variable->scalar;
        } elseif (is_array($variable)) {
            $pieces = array();
            foreach ($variable as $key => $value) {
                $pieces[] = is_numeric($key) ? $value : "$value AS $key";
            }
            $column = implode(", ", $pieces);
        } else {
            $column = $variable;
        }
        return $column;
    }

    public function dbTable()
    {
        $pieces = array($this->db_name, $this->table_name);
        foreach ($pieces as $key => $value) {
            $pieces[$key] = "`$value`";
        }
        return $str = implode(".", $pieces);
    }

    /*
    CRUD
    */
    public function get($column = null, $where = null, $order = null)
    {
        $column = self::columnName($column);
        $table = self::dbTable();

        // 拼接 SQL
        $pieces = array();
        $pieces[] = "SELECT $column";
        $pieces[] = "FROM $table";
        $pieces[] = "WHERE $where";
        $pieces[] = "ORDER BY $order";
        $pieces[] = "LIMIT 1";
        $sql = implode(' ', $pieces);

        // 查询
        $row = static::object($sql);
        return $row;
    }

    public function select($column = null, $where = null, $order = null, $limit = null)
    {
        $column = self::columnName($column);
        $table = self::dbTable();

        $pieces = array();
        $pieces[] = "SELECT $column";
        $pieces[] = "FROM $table";
        $pieces[] = "ORDER BY $order";
        $pieces[] = "LIMIT $limit";
        $sql = implode(' ', $pieces);

        $all = static::all($sql);
        return $all;
    }

    public function sqlSelect($column = null, $where = null, $order = null, $limit = null, $join = null)
    {
        $column = self::columnName($column);
        $table = self::dbTable();
        $variable = array();
        if (is_array($join)) {
            foreach ($join as $key => $value) {
                foreach ($value as $k => $v) {
                    $str = implode(', ', $v);
                    $variable[] = "$key $k ON $str";
                }
            }
        }
        if ($variable) {
            $table .= " A";
        }
        $pieces = array();
        $pieces[] = "SELECT $column";
        $pieces[] = "FROM $table";
        foreach ($variable as $value) {
            $pieces[] = $value;
        }
        if ($order) {
           $pieces[] = "ORDER BY $order";
        }
        if ($limit) {
            $pieces[] = "LIMIT $limit";
        }
        return $sql = implode(PHP_EOL, $pieces);
    }

    public function into($variable)
    {
        $table = self::dbTable();
        $pieces = array();
        foreach ($variable as $key => $value) {
            $value = addslashes($value);
            $pieces[] = "`$key` = '$value'";
        }
        $str = implode(', ', $pieces);
        $sql = "INSERT INTO $table SET $str";
        $row = self::exec($sql);
        return self::lastInsertId();
    }

    public function update($variable, $where)
    {
        $table = self::dbTable();
        $pieces = array();
        foreach ($variable as $key => $value) {
            $value = addslashes($value);
            $pieces[] = "`$key` = '$value'";
        }
        $str = implode(', ', $pieces);
        $pieces = array();
        foreach ($where as $key => $value) {
            $value = addslashes($value);
            $pieces[] = "`$key` = '$value'";
        }
        $wh = implode(', ', $pieces);
        $sql = "UPDATE $table SET $str WHERE $wh";
        $row = self::exec($sql);
        return $row;
    }

    /*
    聚合
    */
    public function max()
    {
        $column = $this->primary_key;
        $table = $this->dbTable();
        $pieces = array();
        $pieces[] = "SELECT MAX(`$column`) AS num";
        $pieces[] = "FROM $table";
        $sql = implode(' ', $pieces);
        $row = self::object($sql);
        return $row->num;
    }
}
