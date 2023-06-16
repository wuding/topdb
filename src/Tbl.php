<?php

namespace Topdb;

use Ext\PDObj;
use Pkg\Glob;
use Pkg\{PFSys};

class Tbl
{
    const VERSION = '23.6.16';

    // 配置
    public static $vars = null;
    public $config_file = null;
    public $config_item = null;
    public $config_db = 'Db';
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
    public $mem = 'Mem';

    // 运行时
    public static $connects = array();
    public static $memories = array();
    public $key = null;
    public $memory_key = null;
    public $sql = array();

    // 编译时
    public $functions = array(
        '__construct' => array(
            // 对错
            'arguments' => array(
                'vars' => null,
                'prop' => null,
                'conf' => null,
                'connect' => null,
            ),
            // 好坏
            'procedure' => array(
                'merge_props' => array(
                ),
            ),
            // 善恶
            'results' => array(
            ),
        ),
        'connect' =>  array(
            // 对错
            'arguments' => array(
                'vars' => null,
            ),
            // 好坏
            'procedure' => array(
                'dsn_results' => array(
                ),
            ),
            // 善恶
            'results' => array(
            ),
        ),
    );

    // unique


    // US = Society for the Prevention of Cruelty to Children 防止虐待儿童协会 fángzhǐ nüèdài értóng xiéhuì
    public function __construct($vars = null, $prop = null, $conf = null, $connect = null)
    {
        $this->$functions[__FUNCTION__]['arguments'] = get_defined_vars();

        $mem = null;
        // 遍历设置属性
        if (is_array($prop)) {
            foreach ($prop as $key => $value) {
                $this->$key = $value;
                $this->$functions[__FUNCTION__]['procedure']['iteration_set_props'][$key] = $value;
            }
        } elseif (is_string($prop) || is_object($prop)) { // 仅设置内存缓存
            $mem = $prop;
        }

        // 导入配置
        $db = $conf[$this->config_db] ?? array();
        $item = $conf[$this->config_item] ?? array();
        $variable = array_merge($db, $item);
        // 合并属性
        foreach ($variable as $key => $value) {
            $val = $this->$key;
            if (!$val) {
                $this->$key = $value;
                $this->$functions[__FUNCTION__]['procedure']['merge_props'][$key] = $value;
            }
        }

        $this->init($vars, $mem, $connect);
    }

    public function __call($name, $arguments)
    {
        if (!self::$connects) {
            return null;
        }

        $obj = self::$connects[$this->key];
        return call_user_func_array(array($obj, $name), $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        $obj = new static();
        return call_user_func_array(array($obj, $name), $arguments);
    }

    // 初始化数据库连接参数、内存缓存类对象
    public function init($vars = null, $mem = null, $connect = null)
    {
        // 设置属性，作为缺省连接参数
        if (null !== $vars) {
            static::$vars = $vars;
        }

        // 保证内存缓存可用
        if (!$this->memory_key && null === $mem) {
            $mem = $this->mem;
            if (!is_string($mem)) {
                $this->mem = null;
            }
        }
        // 设置指针和键值对
        if (null !== $mem) {
            if (is_string($mem)) {
                static::$memories[$mem] = $this->memory_key = $mem;
            } else {
                // 计划：改为毫秒
                $this->memory_key = $key = time();
                static::$memories[$key] = $mem;
            }
        }

        // 连接
        if (false !== $connect) {
            return $this->connect();
        }
    }

    public function connect($vars = null)
    {
        $vars = null === $vars ? static::$vars : $vars;
        $conf = $this->conf($vars);
        $this->$functions[__FUNCTION__]['procedure']['dsn_results'] = $dsn_results = $this->dsn($conf, 'dsn,prefix,variable');

        $username = $conf['username'] ?? null;
        $password = $conf['password'] ?? null;
        $options = $conf['options'] ?? null;
        $arr = array(
            'dsn' => $dsn_results['dsn'],
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

        $dsn_prefix = $dsn_results['prefix'];
        $if_true = 'php' == $dsn_prefix;
        $db_table = array('db_name' => $this->db_name, 'table_name' => $this->table_name);;

        if ($if_true) {
            self::$connects[$key] = $conn = new PFSys($this->$functions, $db_table);
        } else {
            self::$connects[$key] = $conn = new PDObj($dsn_results['dsn'], $username, $password, $options);
        }

        $this->data['connect'] = get_defined_vars();
        return $conn;
    }

    public function conf($vars = null)
    {
        $vars = null === $vars ? array() : $vars;
        $conf = Glob::cnf($this->db_connect, 'database') ?? array();
        $conf = array_merge($conf, $vars);
        $type = ($conf['db_type'] ?? null) ?: 'mysql';
        $data = $this->data[$type] ?: array();
        $data = array_merge($data, $conf);
        $data['dsn_prefix'] = ($data['dsn_prefix'] ?? null) ?: $type;
        return $data;
    }

    public function dsn($variable = null, $var_names = null)
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

        $vars = get_defined_vars();
        return $this->return_results($vars, $var_names);
    }

    public function return_results($variable, $var_names = null)
    {
        if (in_array($var_names, ['', 'all'], true)) {
            return $variable;
        }

        $arr = explode(',', $var_names);
        $count = count($arr);

        if (1 === $count) {
            $results = $variable[$var_names] ?? null;
            return $results;
        }

        $results = array();
        foreach ($arr as $key => $val) {
            $results[$val] = $variable[$val] ?? null;
        }
        return $results;
    }

    /*
    内存缓存
    */
    public function mem($key = null)
    {
        $key = $key ?: $this->memory_key;
        $mem = self::$memories[$key] ?? null;
        if (is_string($mem)) {
            return Glob::get($mem);
        }
        return $mem;
    }

    /*
    拼接
    */
    public function columnName($variable = null)
    {
        if (is_object($variable)) {
            // 字符串转来的对象
            $scalar = $variable->scalar ?? null;
            if ($scalar) {
                $arr = explode(',', $scalar);
            } else { // 还原数组
                $arr = (array) $variable;
            }

            // 遍历列
            foreach ($arr as $key => &$value) {
                $index = is_numeric($key) ? null : "$key.";
                // 共同表前缀
                if (is_array($value)) {
                    foreach ($value as $val) {
                        unset($arr[$key]);
                        $val = trim($val);
                        $arr[] = "$index$val";
                    }
                    continue 1;
                } elseif (is_string($value)) {
                    $value = trim($value);
                    if ($index) {
                        $arr[] = "$index$value";
                        unset($arr[$key]);
                        continue 1;
                    }
                } else {
                    var_dump($value);
                    print_r(array(__FILE__, __LINE__, get_defined_vars()));
                    exit;
                }

                // 分割库表列与别名
                $alias = preg_split("/\s+AS\s+|\s+/i", $value);
                $count = count($alias);
                $pieces = array();
                $nm = null;
                // 有别名
                if (1 < $count) {
                    list($value, $nm) = $alias;
                }
                // 库表列
                $names = preg_split("/\.+/i", $value);
                foreach ($names as $name) {
                    $pieces[] = "`$name`";
                }
                $value = implode('.', $pieces);
                // 拼接别名
                if ($nm) {
                    $value .= " AS `$nm`";
                }
            }
            $column = implode(', ', $arr);
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

    public function sqlSet($data)
    {
        $pieces = [];
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $pieces[] = $value;
                continue 1;
            }
            $type = gettype($value);
            $val = in_array($type, array('integer')) ? $value : "'". addslashes($value) ."'";
            if (null === $value) {
                continue 1;
                $val = is_null($value) ? 'NULL' : $val;
            }
            $pieces[] = "`$key` = $val";
        }
        return $str = implode(','. PHP_EOL, $pieces);
        print_r($str);
    }

    public function sqlWhere($data, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }
        if (!is_array($data)) {
            $str = is_numeric($data) ? "$alias`$this->primary_key` = $data" : $data;
            return $str;
        }

        $pieces = [];
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $pieces[] = $value;
                continue 1;
            } elseif (is_null($value)) {
                $pieces[] = "`$key` IS NULL";
                continue 1;
            }
            $type = gettype($value);
            $valas = addslashes($value);
            $val = in_array($type, array('integer')) ? $value : "\"$valas\"";
            $pieces[] = "`$key` = $val";
        }
        return $str = implode(' AND '. PHP_EOL, $pieces);
    }

    public function sqlGroup()
    {

    }

    public function sqlHaving()
    {

    }

    public function sqlOrder($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $pieces = array();
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $pieces[] = $value;
            } elseif (is_array($value)) {

            } else {
                $pieces[] = "$key $value";
            }
        }
        return $sql = implode(', ', $pieces);
    }

    public function sqlLimit($variable)
    {
        if (!$variable) {
            return;
        }

        if (!is_array($variable)) {
            // 计划：修剪
            return $variable;
        }

        $pieces = $var_array = array();
        foreach ($variable as $key => $value) {
            if (is_numeric($key)) {
                $pieces[] = $value;
            } else {
                // 计划：使用键名
                $var_array[$key] = $value;
            }
        }

        // 计划：限制 2 个
        return $str = implode(', ', $pieces);
    }

    // 通过带键名的数组合成完整语句
    public static function sqlPieces($variable = array())
    {
        $arr = array();
        foreach ($variable as $key => $value) {
            if ($value) {
                $arr[] = "$key $value";
            }
        }
        return $sql = implode(PHP_EOL, $arr);
    }

    // 多行查询的语句
    // 计划：增加 GROUP BY 等支持，支持多个连接
    public function selectSql($column = null, $where = null, $order = null, $limit = 10, $options = array())
    {
        //=f
        $alias = $left_join = null;
        $offset = 0;
        //=z
        $column = self::columnName($column);

        //=sh
        $table = self::dbTable();
        if (is_array($options)) {
            extract($options);
        }

        //=l
        if ($alias) {
            $table .= " $alias";
        }

        //=f
        $pieces = array(
            'SELECT' => $column,
            'FROM' => $table,
            'LEFT JOIN' => $left_join,
            'WHERE' => $this->sqlWhere($where, $alias),
            'ORDER BY' => $this->sqlOrder($order),
            'LIMIT' => $this->sqlLimit($limit),
            'OFFSET' => $offset,
        );
        $this->sql = $sql = self::sqlPieces($pieces);
        return $sql;
    }

    public function sqlInsert()
    {

    }

    public function sqlUpdate()
    {

    }

    public function sqlDelete($column = null, $where = null)
    {
        $table = self::dbTable();
        $pieces = array(
            'DELETE' => $column ?: ' ',
            'FROM' => $table,
            'WHERE' => $this->sqlWhere($where),
        );
        return $sql = self::sqlPieces($pieces);
    }

    /*
    CRUD
    */
    // 单行查询
    public function get($column = null, $where = null, $order = null, $options = array())
    {
        //=f
        $direct = null;
        $returns = $options['returns'] ?? null;

        //=z
        if (is_string($column)) {
            $column = trim($column);
            // 字符串单列直接返回
            if ($column && '*' !== $column) {
                $pos = strpos($column, ',');
                $direct = false === $pos;
            }
        }

        // 拼接 SQL
        $sql = self::selectSql($column, $where, $order, 1, $options);
        if ('sql' === $returns) {
            return $sql;
        }

        // 查询
        $row = self::object($sql);
        // 单列
        if ($row && true === $direct) {
            $subject = trim($column);
            // 别名
            if (preg_match("/\s+/", $subject)) {
                $array = preg_split("/\s+/", $subject);
                $column = array_pop($array);
            }
            $row = $row->$column ?? true;
        }
        return $row;
    }

    // 多行查询
    public function select()
    {
        $param_arr = func_get_args();
        if ('php' === $this->$functions['connect']['procedure']['dsn_results']['prefix']) {
            $obj = self::$connects[$this->key];
            $all = call_user_func_array(array($obj, 'select'), $param_arr);
            return $all;
        }


        $sql = call_user_func_array(array($this, 'selectSql'), $param_arr);

        $all = self::all($sql);
        return $all;
    }

    // 计划：待删
    public function sqlSelect0($column = null, $where = null, $order = null, $limit = null, $join = null)
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

    // 计划：使用拼接方法，区分（批量）插入方法
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

    // 插入单行、批量插入
    public function insert($data, $fields = null)
    {
        $table = self::dbTable();
        $pieces = array(
            'INSERT INTO' => $table,
            'SET' => $this->sqlSet($data),
        );
        $this->sql = $sql = self::sqlPieces($pieces);
        $row = self::exec($sql);
        return self::lastInsertId();
    }

    // 计划：使用拼接方法
    public function update($variable, $where)
    {
        $table = self::dbTable();
        $pieces = array();
        foreach ($variable as $key => $value) {
            // 空值
            if (null === $value) {
                $pieces[] = "`$key` = NULL";
                continue 1;
            }
            // 字符串
            $value = addslashes($value);
            $pieces[] = "`$key` = '$value'";
        }
        if (!$pieces) {
            return false;
        }
        $str = implode(', ', $pieces);
        $pieces = array();
        foreach ($where as $key => $value) {
            $value = addslashes($value);
            $pieces[] = "`$key` = '$value'";
        }
        $wh = implode(' AND ', $pieces);
        $sql = "UPDATE $table SET $str WHERE $wh";
        $row = self::exec($sql);
        return $row;
    }

    public function delete($where = null, $column = null)
    {
        $sql = $this->sqlDelete($column, $where);
        $del = self::exec($sql);
        return $del;
    }

    /*
    补充
    */
    public function has()
    {

    }

    public function exist()
    {

    }

    /*
    内存缓存
    */
    // 从内存读写多行查询
    public function memSelect()
    {
        //=s
        $param_arr = func_get_args();

        //=f
        $ttl = 86400;
        $ns = 'SQL_SELECT';
        $dump_sql = null;
        if (array_key_exists(4, $param_arr)) {
            $options = $param_arr[4];
            if (is_array($options)) {
                if (array_key_exists('ttl', $options)) {
                    $ttl = $options['ttl'];
                    unset($options['ttl']);
                }
                if (array_key_exists('ns', $options)) {
                    $ns = $options['ns'];
                    unset($options['ns']);
                }
                if (array_key_exists('dump_sql', $options)) {
                    $dump_sql = $options['dump_sql'];
                    unset($options['dump_sql']);
                }
            } else {
                $ttl = $options;
                unset($param_arr[4]);
            }
        }

        //=z
        $sql = call_user_func_array(array($this, 'selectSql'), $param_arr);
        $md5 = md5($sql);
        $key = "$ns:$md5";

        //=l
        if ($dump_sql) {
            return get_defined_vars();
        }

        // 不缓存
        if (false === $ttl) {
            return $all = self::all($sql);
        }
        // 负值即删除
        if (0 > $ttl) {
            $del = $this->mem()->del($key);
            return $del;
        }

        //=sh
        $val = $this->mem()->getJSON($key, '__FALSE__');

        //=l
        // 错误：可能缓存的值就是 false
        if ('__FALSE__' !== $val) {
            return $val;
        }

        //=j
        $all = self::all($sql);
        $set = $this->mem()->setJSON($key, $all, $ttl);

        //=g
        return $all;
    }

    // 从内存读写单行查询
    public function memGet($column = null, $where = null, $order = null, $options = array())
    {
        //=f
        $ttl = 86400;
        $ns = 'SQL_GET';
        if (is_array($options)) {
            if (array_key_exists('ttl', $options)) {
                $ttl = $options['ttl'];
                unset($options['ttl']);
            }
            if (array_key_exists('ns', $options)) {
                $ns = $options['ns'];
                unset($options['ns']);
            }
        } else {
            $ttl = $options;
            $options = array();
        }

        //=z
        $sql = call_user_func_array(array($this, 'selectSql'), array($column, $where, $order, 1, $options));
        $md5 = md5($sql);
        $key = "$ns:$md5";

        //=l
        // 不缓存
        if (false === $ttl) {
            $row = self::object($sql);
            return $row;
        }
        // 负值即删除
        if (0 > $ttl) {
            $del = $this->mem()->del($key);
            return $del;
        }

        //=sh
        $val = $this->mem()->getJSON($key, '__FALSE__');

        //=l
        if ('__FALSE__' !== $val) {
            return $val;
        }

        //=j
        // 计划：call
        $row = self::object($sql);
        $set = $this->mem()->setJSON($key, $row, $ttl);

        //=g
        return $row;
    }

    /*
    聚合
    */
    public function max()
    {
        // 计划：列名作为参数
        $column = $this->primary_key;
        $table = $this->dbTable();
        $pieces = array();
        $pieces[] = "SELECT MAX(`$column`) AS num";
        $pieces[] = "FROM $table";
        $sql = implode(' ', $pieces);
        $row = self::object($sql);
        return $row->num;
    }

    public function count()
    {

    }

}
