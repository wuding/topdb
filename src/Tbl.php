<?php

namespace Topdb;

use Ext\PDObj;
use Pkg\Glob;
use Pkg\{PFSys};

class Tbl
{
    const VERSION = 26.0324;
    const REVISION = 58;
    const EDITION = 233500.1750260900;

    // 配置
    public static $vars = null;
    public $config_file = null;
    public $config_name = null;
    public $config_item = null;
    public $config_db = 'Db';
    public $db_connect = null;
    public $db_name = null;
    public $db_origin = null;
    public $db_suffix = null;
    public $table_name = null;
    public $table_origin = null;
    public $table_prefix = null;
    public $table_suffix = null;
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
    public $datum = array();
    static $diff = array();
    static $class = null;
    static $maintenance = null;
    static $sql_str = [];
    var $mem_dbindex = 0;

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
        $this->functions[__FUNCTION__]['arguments'] = get_defined_vars();

        $mem = null;
        // 遍历设置属性
        if (is_array($prop)) {
            foreach ($prop as $key => $value) {
                $this->$key = $value;
                $this->functions[__FUNCTION__]['procedure']['iteration_set_props'][$key] = $value;
            }
        } elseif (is_string($prop) || is_object($prop)) { // 仅设置内存缓存
            $mem = $prop;
        }

        // 导入配置
        $db = $conf[$this->config_db] ?? array();
        $item = $this->_configItem($conf, $this->config_item);
        $variable = array_merge($db, $item);
        // 合并属性
        foreach ($variable as $key => $value) {
            $val = $this->$key;
            if (!$val) {
                $this->$key = $value;
                $this->functions[__FUNCTION__]['procedure']['merge_props'][$key] = $value;
            }
        }

        $this->init($vars, $mem, $connect);
    }

    public function _configItem($conf, $var)
    {
        $class = static::$class;
        if (!is_null($var)) {
            return $conf[$var] ?? array();
        }

        $subject = str_replace('\\', '.', $class);
        $arr = preg_split("#\.src\.model\.#", $subject);
        $pop = array_pop($arr);
        $item = Glob::conf($pop, null, $conf);
        if (is_null($item)) {
            $split = preg_split("#\.#", $pop);
            $count = count($split);
            $name = array_pop($split);
            if (preg_match_all("#([A-Z]+)([a-z0-9]+)#", $name, $matches)) {
                $pieces = $matches[0];
                $str = implode('_', $pieces);
                $tbl = strtolower($str);
                $item = [
                    'table_name' => $tbl,
                ];
            }
        }
        // print_r(get_defined_vars());
        return $item;
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
        $this->functions[__FUNCTION__]['procedure']['dsn_results'] = $dsn_results = $this->dsn($conf, 'dsn,prefix,variable');

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
        $db_table = array('db_name' => $this->db_name, 'table_name' => $this->table_name);

        if ($if_true) {
            self::$connects[$key] = $conn = new PFSys($this->functions, $db_table);
        } elseif (!self::$maintenance) {
            self::$connects[$key] = $conn = new PDObj($dsn_results['dsn'], $username, $password, $options);
        } else {
            self::$connects[$key] = $conn = false;
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
            $variable = trim($variable);
            $column = $variable ?: '*';
        }
        return $column;
    }

    public function _setDbName()
    {
        if (!$this->db_origin) {
            return false;
        }

        $pieces = array(
            $this->db_origin,
            $this->db_suffix,
        );
        $this->db_name = implode('', $pieces);
    }

    public function _setTableName()
    {
        if (!$this->table_origin) {
            return false;
        }

        $pieces = array(
            $this->table_prefix,
            $this->table_origin,
            $this->table_suffix,
        );
        $this->table_name = implode('', $pieces);
    }

    public function dbTable()
    {
        $this->_setDbName();
        $this->_setTableName();
        return $this->dbTableV1();
    }

    public function dbTableV1()
    {
        $pieces = array($this->db_name, $this->table_name);
        foreach ($pieces as $key => $value) {
            $pieces[$key] = "`$value`";
        }
        return $str = implode(".", $pieces);
    }

    public function sqlSet($data, $func = null, $empty = null)
    {
        $pieces = [];
        foreach ($data as $key => $value) {
            if ('' === $key) {
                continue 1;
            }

            if (is_int($key)) {
                if (-1 < $key) {
                    $pieces[] = $value;
                }
                continue 1;
            }

            $type = gettype($value);


            $val = null;
            if (in_array($type, array('integer', 'double')) || is_int($value)) {
                $val = $value;

            } elseif (is_string($value)) {
                $vs = addslashes($value);
                if (!$vs) {
                    if (is_null($empty)) {
                        $vs = null;
                    }
                }

                if (!is_null($vs)) {
                    $val = "'". $vs ."'";
                } else {
                    $value = null;
                }
            } elseif (is_object($value)) {
                $val = $value->scalar ?? null;
            } elseif (!in_array($type, ['NULL'])) {
                var_dump([$type, $value, $key, __LINE__, __FILE__]);
                die;
            }

            if (null === $value) {
                if (null === $func) {
                    continue 1;
                }

                $val = is_null($value) ? 'NULL' : $val;
            }
            $pieces[] = "`$key` = $val";
        }
        return $str = implode(','. PHP_EOL, $pieces);
    }

    function value($value, $func = true, $empty = null)
    {
        $type = gettype($value);
        $val = null;
        if (in_array($type, array('integer')) || is_int($value)) {
            $val = $value;

        } elseif (is_string($value)) {
            $vs = addslashes($value);
            if (!$vs) {
                if (is_null($empty)) {
                    $vs = null;
                }
            }

            if (!is_null($vs)) {
                $val = "'$vs'";
            } else {
                $value = null;
            }
        }

        if (null === $value) {
            if (null === $func) {
                return '__CONTINUE__';
            }
            $val = is_null($value) ? 'NULL' : $val;
        }
        return $val;
    }

    public function sqlWhere($data, $alias = null)
    {
        if (!$data) {
            return null;
        }
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
            $val = in_array($type, array('integer')) ? $value : "'$valas'";
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

        $haystack = ['DOUBLE'];
        $pieces = array();
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $up = strtoupper($value);
                if (in_array($up, $haystack)) {
                    $value = "`$value`";
                }
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
        $sql = self::sqlPieces($pieces);
        return $sql;
    }

    public function selectSqlInnerJoin($sql, $innerJoin, $param_arr)
    {
        if ($sql) {
            self::$sql_str[] = $this->sql[] = $sql;
            return $sql;
        }

        if ($innerJoin) {
            $string = $param_arr[0] ?? null;
            $haystack = explode(',', $string);
            if (in_array($innerJoin, $haystack)) {
                $flip = array_flip($haystack);
                $key = $flip[$innerJoin] ?? null;
                $haystack[$key] = "A.$innerJoin";
            } else {
                $haystack[] = "A.$innerJoin";
            }
            $col = implode(',', $haystack);
            $table = self::dbTable();

            $param_arr[0] = $innerJoin;
            $sql_ij = call_user_func_array(array($this, 'selectSql'), $param_arr);
            $sql = <<<HEREDOC
SELECT $col
FROM $table A
INNER JOIN (
$sql_ij
) B ON A.$innerJoin = B.$innerJoin
HEREDOC;

        } else {
            $sql = call_user_func_array(array($this, 'selectSql'), $param_arr);
        }

        self::$sql_str[] = $this->sql[] = $sql;
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
        $returns = null;
        if (is_array($options)) {
            extract($options);
        }

        // 拼接 SQL
        self::$sql_str[] = $this->sql[] = $sql = self::selectSql($column, $where, $order, 1, $options);
        if ('sql' === $returns) {
            return $sql;
        }

        // 查询
        $options['column'] = $column;
        $options['sql'] = $sql;
        $orig = self::mem_object_orig($options, $where);
        $row = $this->memObject($orig);
        return $row;
    }

    // 多行查询
    public function select()
    {
        $param_arr = func_get_args();
        if ('php' === $this->functions['connect']['procedure']['dsn_results']['prefix']) {
            $obj = self::$connects[$this->key];
            $all = call_user_func_array(array($obj, 'select'), $param_arr);
            return $all;
        }

        $returns = 'memAll';
        $string = $param_arr[0] ?? null;
        $options = $param_arr[4] ?? null;
        $sql = null;
        $innerJoin = null;
        if (is_array($options)) {
            extract($options);
        }

        $sql = $this->selectSqlInnerJoin($sql, $innerJoin, $param_arr);
        if ('sql' === $returns) {
            return $sql;
        }
        $options['sql'] = $sql;
        $orig = self::mem_all_orig($options, $string);
        // print_r(get_defined_vars());
        #die;
        $memAll = $this->memAll($orig);
        $vars = get_defined_vars();
        return $this->return_results($vars, $var_names = $returns);
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
    public function insert($data, $fields = null, $return_sql = null)
    {
        if (!$data) {
            return null;
        }

        $table = self::dbTable();
        $table .= $this->columns($fields);
        $pieces = array(
            'INSERT INTO' => $table,
        );
        if (!$fields) {
            $pieces['SET'] = $this->sqlSet($data, null, '');
            goto _FIN_;
        }
        $pieces['VALUES'] = $this->values($data);

        _FIN_:
        self::$sql_str[] = $this->sql[] = $sql = self::sqlPieces($pieces);
        if ($return_sql) {
            return $sql;
        }
        $this->datum[] = $data;
        $row = self::exec($sql);
        $this->sqlDiff('insert', $sql);
        return self::lastInsertId();
    }

    function values($variable)
    {
        $pieces = [];
        foreach ($variable as $key => $var) {
            $piece = [];
            foreach ($var as $ke => $valu) {
                $piece[] = $this->value($valu);
            }
            $str = implode(', ', $piece);
            $pieces[] = "($str)";
        }
        return implode(','. PHP_EOL, $pieces);
    }

    function columns($variable)
    {
        if (!$variable) {
            return null;
        }

        $pieces = [];
        foreach ($variable as $key => $value) {
            $pieces[] = "`$value`";
        }
        $col = implode(',', $pieces);
        return $str = " ($col)";
    }

    public function sqlDiff($type, $sql, $table = null)
    {
        $table = $table ?: self::dbTable();
        if (!array_key_exists($type, self::$diff)) {
            return Glob::sqlDiff($sql);
        }

        $array = self::$diff[$type];
        if (is_array($array) && !in_array($table, $array)) {
            return Glob::sqlDiff($sql);
        }
    }

    // 计划：使用拼接方法
    public function update($data, $where, $return_sql = null)
    {
        $table = self::dbTable();
        $pieces = array(
            'UPDATE' => $table,
            'SET' => $this->sqlSet($data, 'update'),
            'WHERE' => $this->sqlWhere($where),
        );
        self::$sql_str[] = $this->sql[] = $sql = self::sqlPieces($pieces);
        if (!$where) {
            var_dump([__LINE__, __FILE__, get_defined_vars()]);
            die;
        }

        if ($return_sql) {
            return $sql;
        }
        $update = self::exec($sql);
        return $update;
    }

    public function updates($variable)
    {
        return $this->batch($variable, 'update');
    }

    public function set($column, $value, $where)
    {
        $data = array(
            $column => $value,
        );

        $update = $this->update($data, $where);
        return $update;
    }

    public function sets($variable)
    {
        return $this->batch($variable, 'set');
    }

    public function batch($variable, $func = null)
    {
        $arr = array();
        foreach ($variable as $key => $param_arr) {
            $arr[$key] = call_user_func_array(array($this, $func), $param_arr);
        }
        return $arr;
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
    public function has($where, $column = 'id')
    {
        return $this->get($column, $where);
    }

    public function exist($data, $condition = null, $column = null, $update = array(), $insert  = array(), $op = array())
    {
        $insert_created = 'time';
        $var_array = $data[''] ?? null;
        if (is_array($var_array)) {
            extract($var_array);
        }

        $column = $column ?: $this->primary_key;
        $where = array();
        if ($condition) {
            if (is_numeric($condition)) {
                $where = $condition;
            } elseif (is_string($condition)) {
                $variable = explode(',', $condition);
                foreach ($variable as $key) {
                    if (array_key_exists($key, $data)) {
                        $where[$key] = $data[$key];
                    }
                }
            } elseif (is_array($condition)) {
                foreach ($condition as $key => $value) {
                    if (is_numeric($key)) {
                        if (array_key_exists($value, $data)) {
                            $where[$value] = $data[$value];
                        }
                    } else {
                        $where[$key] = $value;
                    }
                }
            } elseif (is_object($condition)) {
                // 字符串转来的对象
                $where = $condition->scalar ?? null;
            }
        } else {
            $where = $data;
        }

        $row = $this->get($column, $where);
        if (false !== $row) {
            if ($update) {
                $up = $this->update($data, $row);
                $arr = array();
                foreach ($update as $key) {
                    $arr[$key] = $$key;
                }
                return $arr;
            }
            return $row;
        }

        if (in_array('insert', $op)) {
            return $row;
        }

        if ($insert_created) {
            $created = time();
            if ('time' !== $insert_created) {
                $created = date($insert_created);
            }
            $data['created'] = $created;
        }

        $data = array_merge($data, $insert);
        $ins = $this->insert($data);
        return $ins;
    }

    /*
    内存缓存
    */

    public function row($sql, $col, $single_row)
    {
        if (self::$maintenance) {
            return false;
        }
        $row = self::object($sql);
        return $this->single_row($row, $col, $single_row, 1);
    }

    public function rows($sql, $col, $single_row)
    {
        if (self::$maintenance) {
            return false;
        }
        $all = self::all($sql);
        return $this->single_row($all, $col, $single_row);
    }

    static function memKey($sql, $ns = 'SELECT', $hash = null, $len = null)
    {
        $md5 = $hash ?: md5($sql);
        if (!$hash && is_int($len)) {
            $md5 = substr($md5, 0, $len);
        }
        return $key = "$ns:$md5";
    }

    public function memAll(&$orig, $sql = null, $ttl = null, $ns = null, $column = null, $single_row = null, $db = null, $type = null)
    {
        $time_to_live = null;
        $return_del = null;
        $return_false = null;
        $opt = [];
        $filename = null;
        extract($orig);
        unset($orig);

        // 不缓存
        if (false === $ttl || is_null($ttl)) {
             $all = $this->rows($sql, $column, $single_row);
             $this->sqlDiff('select', $sql);
             return $all;
        }

        $key = self::memKey($sql, $ns);
        $mem = $this->mem();
        $sel = $mem->select($db);

        // 负值即删除
        if (0 > $ttl) {
            $del = $this->mem()->del($key);
            $reset_db = $mem->db();
            if ($return_del) {
                return $del;
            }
            goto __ROW__;
        }

        //=sh
        $val = $this->mem()->getJSON($key, '__FALSE__');

        //=l
        // 错误：可能缓存的值就是 false
        if ('__FALSE__' !== $val) {
            $reset_db = $mem->db();
            return $this->mem_result($val, $type, []);
        } elseif ($return_false) {
            return $val;
        }

        //=j
        __ROW__:
        $all = $this->rows($sql, $column, $single_row);
        $this->sqlDiff('select', $sql);
        if (!$all && !is_null($time_to_live)) {
            if (false === $time_to_live) {
                return $all;
            }
            if (is_int($time_to_live)) {
                $ttl = $time_to_live;
            }
        }
        if (self::$maintenance) {
            $ttl = -1;
        }
        if (0 > $ttl) {
            return $all;
        }

        $arr = $this->mem_sql_result($opt, $sql, $all, $type);
        $set = $this->mem()->setJSON($key, $arr, $ttl);
        $reset_db = $mem->db();

        // mem cache file
        $file_put_contents = $filename ? file_put_contents($filename, json_encode($arr)) : null;

        //=g
        return $all;
    }

    public function memObject(&$orig, $sql = null, $ttl = null, $ns = null, $false = null, $column = null, $hash = null, $len = null, $single_row = null, $db = null, $type = null)
    {
        $time_to_live = null;
        $return_del = null;
        $opt = [];
        extract($orig);
        unset($orig);

        //=l
        // 不缓存
        if (false === $ttl || is_null($ttl)) {
            $row = $this->row($sql, $column, $single_row);
            $this->sqlDiff('get', $sql);
            return $row;
        }

        $key = self::memKey($sql, $ns, $hash, $len);
        $mem = $this->mem();
        $sel = $mem->select($db);

        // 负值即删除
        if (0 > $ttl) {
            $del = $this->mem()->del($key);
            $reset_db = $mem->db();
            if ($return_del) {
                return $del;
            }
            goto __ROW__;
        }

        //=sh
        $val = $this->mem()->getJSON($key, '__FALSE__');

        //=l
        if ('__FALSE__' !== $val) {
            $reset_db = $mem->db();
            return $this->mem_result($val, $type, false);
        }

        //=j
        // 计划：call
        __ROW__:
        $row = $this->row($sql, $column, $single_row);
        $this->sqlDiff('get', $sql);
        if (!$row && !is_null($time_to_live)) {
            if (false === $time_to_live) {
                return $row;
            }
            if (is_int($time_to_live)) {
                $ttl = $time_to_live;
            }
        }
        if ($false && false === $row) {
            return $row;
        }
        if (self::$maintenance) {
            $ttl = -1;
        }
        if (0 > $ttl) {
            return $row;
        }

        $opt['count_val'] = 1;
        $arr = $this->mem_sql_result($opt, $sql, $row, $type);
        $set = $this->mem()->setJSON($key, $arr, $ttl);
        $reset_db = $mem->db();
        return $row;
    }

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
             $all = self::all($sql);
             $this->sqlDiff('select', $sql);
             self::$sql_str[] = $this->sql[] = $sql;
             return $all;
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
        $this->sqlDiff('select', $sql);
        self::$sql_str[] = $this->sql[] = $sql;

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

    public function count($where = null, $column = null)
    {
        $column = $column ?: '*';
        $select = "COUNT($column) AS num";
        $table = $this->dbTable();

        $pieces = array(
            'SELECT' => $select,
            'FROM' => $table,
            'WHERE' => $this->sqlWhere($where),
        );
        self::$sql_str[] = $this->sql[] = $sql = self::sqlPieces($pieces);
        $row = self::object($sql);
        return $row->num;
    }

/*
函数
*/
    public function direct($column)
    {
        $direct = null;
        if (is_string($column)) {
            $column = trim($column);
            // 字符串单列直接返回
            if ($column && '*' !== $column) {
                $pos = strpos($column, ',');
                $direct = false === $pos;
            }
        }
        return $direct;
    }

    public function single_row($all, $column, $single_row, $one = null)
    {
        if (!$all || !$single_row) {
            return $all;
        }

        $direct = $this->direct($column);
        if ($all && true === $direct) {
            $subject = trim($column);
            // 别名
            if (preg_match("/\s+/", $subject)) {
                $array = preg_split("/\s+/", $subject);
                $column = array_pop($array);
            }

            // 单列
            if ($one) {
                $row = $this->single_col($all, $column);
                return $row;
            }

            $arr = [];
            foreach ($all as $key => $value) {
                $row = $this->single_col($value, $column);
                $arr[] = $row;
            }
            return $arr;
        }
        return $all;
    }

    public function single_col($row, $column)
    {
        if (is_object($row)) {
            $row = $row->$column ?? true;
        } elseif (is_array($row)) {
            $row = $row[$column] ?? true;
        }
        return $row;
    }

    public function hash($hash, $json, $where, $ns = null)
    {
        if (0 === $json && $where) {
            $wh = $where;
            if (is_array($where)) {
                $count = count($where);
                if (1 === $count) {
                    foreach ($where as $key => $value) {
                        if (is_numeric($key)) {
                            $wh = $value;
                        } else {
                            $wh = $value;
                        }
                    }
                }
            }
            $subject = json_encode($wh);
            $pr = preg_replace("#[\{\}\[\]\"]+#", '', $subject);
            $hash = str_replace(':', '_', $pr);
        }
        return $hash;
    }

/*
function
*/

    function mem_all_orig($options, $string)
    {
        $ttl = null;
        $sql = null;
        $column = self::columnName($string);
        $single_row = null;
        $ns = 'SELECT';
        $db = $this->mem_dbindex;
        $type = null;
        $time_to_live = null;
        if (is_array($options)) {
            extract($options);
        }
        unset($options, $string);
        return get_defined_vars();
    }

    function mem_object_orig($options, $where = null)
    {
        $ttl = null;
        $sql = null;
        $column = '*';
        $single_row = true;
        $ns = 'GET';
        $hash = null;
        $len = null;
        $db = $this->mem_dbindex;
        $type = null;
        $time_to_live = null;
        $false = null;
        $json = null;
        if (is_array($options)) {
            extract($options);
        }
        $hash = $this->hash($hash, $json, $where, $ns);
        unset($options);
        return get_defined_vars();
    }

    function mem_result($result, $type = null, $value = null)
    {
        if ($type) {
            $var = $result->data ?? $value;
            if (is_null($var)) {
                return $result;
            }
            return $var;
        }
        return $result;
    }

    function mem_sql_result(&$orig, $sql, $result, $type = null)
    {
        $count_val = -1;
        $countable = null;
        extract($orig);
        if (!$type) {
            return $result;
        }

        $is_countable = is_null($countable) ? is_countable($result) : $countable;
        $count = $is_countable ? count($result) : $count_val;
        $sql_plain = preg_replace("#[\r\n]#", ' ', $sql);
        $arr = [
            'info' => [
                'count' => $count,
                'sql' => $sql_plain,
                'type' => $type,
            ],
            'page' => null,
            'user' => null,
            'data' => $result,
        ];
        $array_merge = array_merge($arr, $orig[''] ?? []);
        unset($result, $arr);
        return $array_merge;
    }
}
