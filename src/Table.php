<?php

namespace Topdb;

use Ext\Yac;

class Table
{
    const VERSION = '20.213.103';
    public $adapter = null;
    public $fields = '';
    public static $data = array(
        'config' => [],
        'name' => 'wuding/topdb',
    );
    public static $names = array(
        'wuding/topdb' => 'Topdb',
        'catfan/medoo' => 'Medoo',
    );

    public $return = null;
    public $logs = [];
    public $join = null;
    public $group_by = null;
    public $having = null;
    public $exist_fields = [];
    public $null = null;

    /*
    +---------------------------------------
    + 基本
    +---------------------------------------
    */

    public function __construct($config = [], $name = null)
    {
        $this->inst($config);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->adapter, $name), $arguments);
    }

    public function getVars()
    {
        $variable = ['table_name', 'fields', 'dbname' => 'db_name', 'port'];
        $vars = [];
        foreach ($variable as $key => $value) {
            if (!isset($this->$value)) {
                continue 1;
            }
            if (is_numeric($key)) {
                $vars[$value] = $this->$value;
            } else {
                $vars[$key] = $this->$value;
            }
        }
        return $vars;
    }

    public function inst($config = [])
    {
        $option = array(
            'dbname' => $this->db_name,
        );
        $options = array_merge($config, $option);
        $this->initAdapter($options);
    }

    public function initAdapter($options = [], $config = [], $name = null)
    {
        $config = $config ? : self::$data['config'];
        $name = $name ? : self::$data['name'];
        $names = self::$names;
        if (array_key_exists($name, $names)) {
            $vars = $this->getVars();
            foreach ($vars as $key => $value) {
                $config[$key] = $value;
            }
            $file = $names[$name];
            $class = "\\Topdb\\Adapter\\$file";
            $this->adapter = new $class($config, $options);
            #$this->adapter->setVars($vars);
            $this->db = $this->adapter->database;
        }
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

    /*
    +---------------------------------------
    + 覆盖
    +---------------------------------------
    */

    public function exec($statement)
    {
        return $this->adapter->exec($statement);
    }

    public function query()
    {
        $arr = call_user_func_array([$this->adapter, 'query'], func_get_args());
        if (!is_array($arr)) {
            return $arr;
        }
        $count = count($arr);
        if (1 < $count) {
            return $arr;
        }
        return array_shift($arr);
    }

    /*
    +---------------------------------------
    + 拼接
    +---------------------------------------
    */

    public function from($name = null)
    {
        return $name = $name ? : $this->db_name . '.' . $this->table_name;
    }

    public function sqlColumns($column = '*')
    {
        if (is_array($column)) {
            return implode(',', $column);
        }
        return $column;
    }

    public function sqlSet($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $arr = [];
        $haystack = $this->null ?? [];
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $arr[] = $value;
            } elseif ($key) {
                $val = 'NULL';
                // 等同 null
                if (in_array($value, $haystack, true)) {
                    foreach ($haystack as $k => $v) {
                        if ($value === $v) {
                            $value = null;
                            break;
                        }
                    }
                }
                // 分类型生成
                if (is_array($value)) {
                    $val = implode('', $value);
                } elseif (is_numeric($value)) {
                    $val = $value;
                } elseif (null !== $value) {
                    $value = addslashes($value);
                    $val = "'$value'";
                }
                $arr[] = "`$key` = $val";
            }
        }
        return $str = implode(", ", $arr);
    }

    public function sqlWhere($where, $type = 'AND')
    {
        if (!is_array($where)) {
            $where = is_numeric($where) ? "`$this->primary_key` = $where" : $where;
            return $where;
        }

        $arr = [];
        foreach ($where as $key => $value) {
            // 没有列名的直接写SQL语句
            if (is_numeric($key)) {
                $arr[] = $value;

            // 多条件
            } elseif (preg_match('/^(ADN|OR)$/', $key, $matches)) {
                print_r([$matches, __FILE__, __LINE__]);
                exit;
            } elseif (preg_match('/^(NOT|LIKE)\s+/i', $value, $matches)) {
                $arr[] = "`$key` $value";
            } elseif (is_array($value)) {
                print_r([$value, __FILE__, __LINE__]);
                exit;
            } elseif (is_numeric($value)) {
                $arr[] = "`$key` = $value";
            } elseif (null === $value) {
                $arr[] = "`$key` IS NULL";
            } else {
                $value = addslashes($value);
                $arr[] = "`$key` = '$value'";
            }
        }
        return $str = implode(" $type ", $arr);
    }

    /*
    +---------------------------------------
    + CRUD
    +---------------------------------------
    */

    public function update($set = [], $where = null, $order = null, $limit = null, $call = null)
    {
        $db_table = $this->from();
        $sql = "UPDATE $db_table SET ";
        $sql .= $this->sqlSet($set);

        $condition = '';
        $whereSql = $this->sqlWhere($where);
        if ($whereSql) {
            $condition .= " WHERE $whereSql";
        }
        if ($order) {
            $condition .= " ORDER BY $order";
        }
        if (null !== $limit) {
            $condition .= " LIMIT $limit";
        }
        $sql .= $condition;

        $arr = array('exec' => $this->exec($sql));
        $exec = $this->logs($sql, $call ?: 'update', $arr) ?: $arr;
        return $exec;
    }

    public function select($where = null, $column = '*', $order = null, $limit = 10, $offset = null)
    {
        $column = $column ?: '*';
        $db_table = $this->from();
        $sql = "SELECT $column FROM $db_table";
        if ($this->join) {
            $sql .= " $this->join";
        }

        $where = $this->sqlWhere($where);
        if ($where) {
            $sql .= " WHERE $where";
        }

        if ($this->group_by) {
            $sql .= " GROUP BY $this->group_by";
            if ($this->having) {
                $sql .= " HAVING $this->having";
            }
        }

        if ($order) {
            $sql .= " ORDER BY $order";
        }
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        if (null !== $offset) {
            $sql .= " OFFSET $offset";
        }
        $arr = $this->adapter->select($sql);
        return $this->logs($sql, 'select', $arr) ?: $arr;
    }

    public function queryAll($sql)
    {
        return $arr = $this->query($sql, \PDO::FETCH_OBJ);
    }

    /*
    +---------------------------------------
    + 批量或其他
    +---------------------------------------
    */

    public function _get($where = null, $column = null, $order = null, $group = [], $join = null)
    {

    }

    public function get($where = null, $column = null, $order = null, $limit = 1, $call = null)
    {
        $column = $column ? : ($this->primary_key ? : '*');
        $db_table = $this->from();
        $column = $this->sqlColumns($column);
        $sql = "SELECT $column FROM $db_table";
        $where = $this->sqlWhere($where);
        if ($where) {
            $sql .= " WHERE $where";
        }
        if ($order) {
            $sql .= " ORDER BY $order";
        }
        $sql .= " LIMIT $limit";
        return $this->logs($sql, $call ? : 'get') ? : $this->adapter->get($sql);
    }

    public function find($id)
    {
        return call_user_func_array([$this, 'get'], func_get_args());
    }

    public function into($field = null, $value = null)
    {
        $db_table = $this->from();

        if (is_string($field)) {
            $field = explode(',', $field);
        }
        if (is_array($value) && $value) {

        } else {
            $value = [[]];
        }

        $count = count($field);
        $field = implode('`, `', $field);
        $field = '(`' . $field . '`)';

        $values = [];
        foreach ($value as $row) {
            $arr = [];
            for ($i = 0; $i < $count; $i++) {
                $val = isset($row[$i]) ? $row[$i] : null;
                if (null != $val) {
                    $val = addslashes($val);
                    $val = "'$val'";
                } else {
                    $val = 'NULL';
                }
                $arr []= $val;
            }
            $data = implode(', ', $arr);
            $data = '(' . $data . ')';
            $values []= $data;
        }
        $values = implode(', ', $values);

        $sql = "INSERT INTO $db_table $field VALUES ";
        $sql .= $values;
        $arr = $this->db->insert($sql);
        return $this->logs($sql, 'into', $arr) ?: $arr;
    }

    /*
    +---------------------------------------
    + 聚合
    +---------------------------------------
    */

    public function count($where = null, $column_name = null, $cache = null)
    {
        $column = null === $column_name ? ($this->primary_key ? : 0) : $column_name;
        $db_table = $this->from();
        $sql = "SELECT COUNT($column) AS num FROM $db_table";
        $where = $this->sqlWhere($where);
        if ($where) {
            $sql .= " WHERE $where";
        }
        if ($cache) {
            extract(Yac::hash($sql, 'count_'));
            if (false !== $cacheValue) {
                return $cacheValue;
            }
        }
        $row = $this->logs($sql, 'count') ? : $this->adapter->get($sql);
        $num = is_object($row) ? $row->num : $row;
        if ($cache && is_numeric($num)) {
            $stored = Yac::store($cacheKey, $num, $cache);
        }
        return $num;
    }

    /*
    +---------------------------------------
    + 补充
    +---------------------------------------
    */

    public function logs($sql, $type = null, $arr = null)
    {
        if (is_array($this->return)) {
            if (in_array($type, $this->return)) {
                $this->logs[] = $sql;
                if (is_array($arr)) {
                    $arr['sql'] = $sql;
                    return $arr;
                }
            }
        } elseif (is_string($this->return) && $type === $this->return) {
            return $sql;
        }
        return false;
    }
}
